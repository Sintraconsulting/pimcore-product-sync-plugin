<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ExternalImageInfo;
use Pimcore\Model\DataObject\Fieldcollection\Data\ImageInfo;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Image;

/**
 * Operator that add a new external image to the product given the url
 *
 * @author Sintra Consulting
 */
class FieldCollectionImageLinkOperator extends AbstractOperator {

    private $additionalData;

    public function __construct(\stdClass $config, $context = null) {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData, true);
    }

    /**
     * Get the column value and excape the HTML tags.
     * Properly set the obtained string to the specific field 
     * passed as additional data for the operator
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {

        $imageurl = $rowData[$colIndex];

        if (method_exists($target, "getImages") && $this->is_url($imageurl)) {
            $images = $target->getImages() != null ? $target->getImages() : new Fieldcollection();

            $imageurl = $rowData[$colIndex];
            $filename = basename($imageurl);

            $classDefinition = ClassDefinition::getByName($target->getClassName());
            $fieldCollectionType = $classDefinition->getFieldDefinition('images')->getAllowedTypes()[0];

            switch ($fieldCollectionType) {
                case "ExternalImageInfo":
                    $this->importExternalImage($target, $images, $imageurl, $filename);
                    break;

                case "ImageInfo":
                    $this->importImage($target, $images, $imageurl, $filename);
                    break;

                default:
                    break;
            }
        }
    }
    
    private function is_url($string) {
        $domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN
        return preg_match("~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string); //! restrict path, query and fragment characters
    }

    /**
     * 
     * @param Concrete $target
     * @param Fieldcollection $images
     * @param string $imageurl
     * @param string $filename
     */
    private function importExternalImage(&$target, $images, $imageurl, $filename) {
        $externalImage = new ExternalImage();
        $externalImage->setUrl($imageurl);

        /**
         * In this phase, just use the file size as image hash.
         * This may change in future
         */
        $headers = get_headers($imageurl, true);
        $hash = $headers['Content-Length'];

        $imageInfo = $this->retrieveExternalImageInfo($images, $filename);

        if ($imageInfo == null) {
            $imageInfo = new ExternalImageInfo();
            $imageInfo->setFilename($filename);

            $imageInfo->setImageurl($externalImage);
            $imageInfo->setHash($hash);
            $imageInfo->setLastsync(date("Y-m-d H:i:s"));
            $imageInfo->setLastupdate(date("Y-m-d H:i:s"));

            $images->add($imageInfo);
        } else {
            $imageInfo->setImageurl($externalImage);
            $imageInfo->setHash($hash);
            $imageInfo->setLastsync(date("Y-m-d H:i:s"));
            $imageInfo->setLastupdate(date("Y-m-d H:i:s"));
        }

        $target->setImages($images);
    }

    /**
     * Retrieve product's ImageInfo for the current image if exists
     *
     * @param Fieldcollection $productImages
     * @param String $filename
     * @return ExternalImageInfo|null
     */
    private function retrieveExternalImageInfo($productImages, $filename) {
        $imageInfo = null;

        foreach ($productImages as $productImageInfo) {
            if (strtolower($productImageInfo->getFilename()) == strtolower($filename)) {
                $imageInfo = $productImageInfo;
                break;
            }
        }

        return $imageInfo;
    }

    /**
     * 
     * @param Concrete $target
     * @param Fieldcollection $images
     * @param string $imageurl
     * @param string $filename
     */
    private function importImage(&$target, $images, $imageurl, $filename){
        $imageInfo = $this->retrieveImageInfo($images, $filename);
        
        if($imageInfo == null){
            $imageInfo = new ImageInfo();
            
            $image = $this->saveAsset($target, $imageurl, $filename);
            $imageInfo->setImage($image);
            
            $images->add($imageInfo);
        }else{
            $image = $imageInfo->getImage();
            
            $image->setData(file_get_contents($imageurl));
            $image->save();
        }
        
        $target->setImages($images);
    }
    
    /**
     * Retrieve product's ImageInfo for the current image if exists
     *
     * @param Fieldcollection $productImages
     * @param String $filename
     * @return ImageInfo|null
     */
    private function retrieveImageInfo($productImages, $filename) {
        $imageInfo = null;

        foreach ($productImages as $productImageInfo) {
            if($productImageInfo instanceof ImageInfo){
                $image = $productImageInfo->getImage();
                
                if(strtolower($image->getFilename()) == strtolower($filename)){
                    $imageInfo = $productImageInfo;
                    break;
                }
            }
        }

        return $imageInfo;
    }
    
    /**
     * 
     * @param Concrete $target
     * @param string $filename
     * @return Image
     */
    private function saveAsset($target, $imageurl, $filename) {
        $assetFolder = Folder::getByPath("/".$target->getClassName());

        if ($assetFolder === null) {
            $assetFolder = new Folder();
            $assetFolder->setParent(Folder::getByPath("/"));
            $assetFolder->setFilename($target->getClassName());
            $assetFolder->save();
        }

        $image = new Image();
        
        $image->setData(file_get_contents($imageurl));
        $image->setParent($assetFolder);
        $image->setFilename($filename);
        
        $headers = get_headers($imageurl, 1);
        $image->setMimetype($headers["Content-Type"]);

        if(method_exists($target, "getName")){
            $image->addMetadata("title", "input", $target->getName());
            $image->addMetadata("alt", "input", $target->getName());
        }

        $image->save();

        return $image;
    }

}

<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ExternalImageInfo;
use Pimcore\Model\DataObject\Data\ExternalImage;
/**
 * Operator that add a new external image to the product given the url
 *
 * @author Sintra Consulting
 */
class ExternalImageLinkOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Get the column value and excape the HTML tags.
     * Properly set the obtained string to the specific field 
     * passed as additional data for the operator
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $imageurl = $rowData[$colIndex];
        
        if(method_exists($target, "getImages") && $this->is_url($imageurl)){
            $images = $target->getImages() != null ? $target->getImages() : new Fieldcollection();
            
            $imageurl = $rowData[$colIndex];
            $filename = basename($imageurl);
            
            $externalImage = new ExternalImage();
            $externalImage->setUrl($imageurl);
            
            /**
             * In this phase, just use the file size as image hash.
             * This may change in future
             */
            $headers = get_headers($imageurl, true);
            $hash = $headers['Content-Length'];

            $imageInfo = $this->retrieveImageInfo($images, $filename);
            
            if($imageInfo == null){
                $imageInfo = new ExternalImageInfo();
                $imageInfo->setFilename($filename);
                
                $imageInfo->setImageurl($externalImage);
                $imageInfo->setHash($hash);
                $imageInfo->setLastsync(date("Y-m-d H:i:s"));
                $imageInfo->setLastupdate(date("Y-m-d H:i:s"));
                
                $images->add($imageInfo);
            }else{
                $imageInfo->setImageurl($externalImage);
                $imageInfo->setHash($hash);
                $imageInfo->setLastsync(date("Y-m-d H:i:s"));
                $imageInfo->setLastupdate(date("Y-m-d H:i:s"));
            }
            
            $target->setImages($images);
        }

    }
    
    /**
     * Retrieve product's ImageInfo for the current image if exists
     *
     * @param Fieldcollection $productImages
     * @param String $filename
     * @return ExternalImageInfo|null
     */
    private function retrieveImageInfo($productImages, $filename){
        $imageInfo = null;

        foreach ($productImages as $productImageInfo) {
            if(strtolower($productImageInfo->getFilename()) == strtolower($filename)){
                $imageInfo = $productImageInfo;
                break;
            }
        }

        return $imageInfo;
    }
    
    private function is_url($string) {
	$domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN
	return preg_match("~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string); //! restrict path, query and fragment characters
    }

}

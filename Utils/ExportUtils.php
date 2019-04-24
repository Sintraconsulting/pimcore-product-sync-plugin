<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Country;
use Pimcore\Model\DataObject\ClassDefinition\Data\Countrymultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Language;
use Pimcore\Model\DataObject\ClassDefinition\Data\Languagemultiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionAbstractData;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstractData;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;


/**
 * Export utils
 *
 * @author Sintra Consulting
 */
class ExportUtils {

    private static $simpleTypes = array("string", "int", "float", "double", "boolean");

    public static function getSimpleTypes() {
        return self::$simpleTypes;
    }

    public static function exportProduct(&$response, Product $product) {

        $productId = $product->getId();
        
        $productExport = array(
            "id" => $productId,
            "created at" => date("Y-m-d H:i:s", $product->getCreationDate()),
            "modified at" => date("Y-m-d H:i:s", $product->getModificationDate())
        );

        $productClassDefinition = ClassDefinition::getByName("Product");

        $productFields = $productClassDefinition->getFieldDefinitions();

        foreach ($productFields as $fieldDefinition) {
            self::exportObjectField($productId, $product, $fieldDefinition, $productExport);
        }

        $response[] = $productExport;
    }

    /**
     * 
     * @param int $productId
     * @param Concrete|FieldcollectionAbstractData|ObjectbrickAbstractData $object
     * @param Data $fieldDefinition
     * @param array $objectExport
     */
    private static function exportObjectField(int $productId, $object, Data $fieldDefinition, array &$objectExport, int $level = 0) {
        $objectReflection = new \ReflectionObject($object);
        
        $fieldName = $fieldDefinition->getName();

        $getterMethod = $objectReflection->getMethod("get" . ucfirst($fieldName));
        $fieldValue = $getterMethod->invoke($object);

        $fieldType = $fieldDefinition->getFieldtype();

        switch ($fieldType) {
            case "wysiwyg":
                $objectExport[$fieldName] = htmlentities($fieldValue);
                break;
            
            case "quantityValue":
            case "inputQuantityValue":
                $objectExport[$fieldName] = self::exportQuantityValueField($fieldValue);
                break;

            case "date":
                $objectExport[$fieldName] = date("Y-m-d", strtotime($fieldValue));
                break;
            
            case "datetime":
                $objectExport[$fieldName] = date("Y-m-d H:i:s", strtotime($fieldValue));
                break;

            case "booleanSelect":
            case "select":
            case "multiselect":
            case "country":
            case "countrymultiselect":
            case "language":
            case "languagemultiselect":
                $objectExport[$fieldName] = self::exportSelectField($fieldValue, $fieldDefinition);
                break;

            case "rgbaColor":
                $objectExport[$fieldName] = self::exportRgbaColorField($fieldValue);
                break;

            case "manyToOneRelation":
                $objectExport[$fieldName] = self::exportRelationField($productId, $fieldValue, $level);
                break;

            case "manyToManyObjectRelation":
            case "manyToManyRelation":
                $objectExport[$fieldName] = self::exportMultipleRelationsField($productId, $fieldValue, $level);
                break;
            
            case "advancedManyToManyObjectRelation":
            case "advancedManyToManyRelation":
                $objectExport[$fieldName] = self::exportAdvancedMultipleRelationsField($productId, $fieldValue, $level);
                break;

            case "localizedfields":
                $objectExport[$fieldName] = self::exportLocalizedField($fieldValue, $fieldDefinition);
                break;

            case "fieldcollections":
                $objectExport[$fieldName] = self::exportFieldcollection($productId, $level, $fieldValue);
                break;
            
            case "objectbricks":
                $objectExport[$fieldName] = self::exportObjectBrick($productId, $level, $fieldValue);
                break;
            
            case "image":
                $objectExport[$fieldName] = self::exportImageField($fieldValue);
                break;
            
            case "externalImage":
                $objectExport[$fieldName] = self::exportExternalImageField($fieldValue);
                break;
            
            case "hotspotimage":
                $objectExport[$fieldName] = self::exportHotspotImageField($fieldValue);
                break;
            
            case "imageGallery":
                $objectExport[$fieldName] = self::exportImageGalleryField($fieldValue);
                break;
            
            case "video":
                $objectExport[$fieldName] = self::exportVideoField($fieldValue);
                break;

            default:
                $realType = $fieldDefinition->getPhpdocType();

                if (in_array($realType, self::getSimpleTypes())) {
                    $objectExport[$fieldName] = $fieldValue;
                } else {
                    Logger::warn("WARNING - exportObjectField - Field type '$fieldType' not supported for export");
                }

                break;
        }
    }

    private static function exportQuantityValueField(QuantityValue $fieldValue){
        return array(
            "value" => $fieldValue->getValue(),
            "unit" => $fieldValue->getUnit()->getAbbreviation()
        );
    }
    
    private static function exportSelectField($fieldValue, Data $fieldDefinition){
        if(!($fieldDefinition instanceof Select || $fieldDefinition instanceof Multiselect || $fieldDefinition instanceof BooleanSelect
                || $fieldDefinition instanceof Country || $fieldDefinition instanceof Countrymultiselect 
                || $fieldDefinition instanceof Language || $fieldDefinition instanceof Languagemultiselect)){
            throw new \Exception("ERROR - exportSelectField - Invalid type '".$fieldDefinition->getFieldtype().
                    "'. Expected one between 'booleanSelect','select', 'multiselect', 'country', 'countrymultiselect', 'language' and 'languagemultiselect'");
        }
        
        $options = $fieldDefinition->getOptions();
        
        if(is_array($fieldValue)){
            $values = array();
            
            foreach ($fieldValue as $value) {
                $option = array_search($value, array_column($options, "value"));
                if($option !== FALSE){
                    $values[] = $options[$option];
                }else{
                    Logger::warn("WARNING - exportSelectField - Invalid field value '$value' for '".$fieldDefinition->getName()."'");
                }
            }
        }else{
            $option = array_search($fieldValue, array_column($options, "value"));
            if($option !== FALSE){
                $values = $options[$option];
            }else{
                Logger::warn("WARNING - exportSelectField - Invalid field value '$fieldValue' for '".$fieldDefinition->getName()."'");
            }
        }
        
        return $values;
    }
    
    private static function exportRgbaColorField(RgbaColor $fieldValue){
        return array(
            "rgb" => $fieldValue->getRgb(),
            "rgba" => $fieldValue->getRgba(),
            "hex" => $fieldValue->getHex(false, true),
            "hexa" => $fieldValue->getHex(true, true),
        );
    }
    
    /**
     * 
     * @param int $productId
     * @param Concrete[] $fieldValue
     * @return array
     */
    private static function exportMultipleRelationsField(int $productId, $fieldValue, int $level){
        $relatedObjects = array();
        
        foreach ($fieldValue as $value) {
            $relatedObjects[] = self::exportRelationField($productId, $value, $level);
        }
        
        return $relatedObjects;
    }
    
    /**
     * 
     * @param int $productId
     * @param ObjectMetadata[] $fieldValue
     * @return array
     */
    private static function exportAdvancedMultipleRelationsField(int $productId, $fieldValue, int $level){
        $relatedObjects = array();
        
        foreach ($fieldValue as $value) {
            $relatedObject = self::exportRelationField($productId, $value->getElement(), $level);
            $relatedObject["metadata"] = $value->getData();
            
            $relatedObjects[] = $relatedObject;
        }
        
        return $relatedObjects;
    }
    
    private static function exportRelationField(int $productId, $fieldValue, int $level){
        if($fieldValue instanceof Concrete && !($fieldValue instanceof TargetServer)){
            $relatedObject = array(
                "id" => $fieldValue->getId(),
                "class" => $fieldValue->getClassName(),
                "created at" => date("Y-m-d H:i:s", $fieldValue->getCreationDate()),
                "modified at" => date("Y-m-d H:i:s", $fieldValue->getModificationDate())
            );

            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && ($fieldValue->getId() != $productId || $fieldValue->getClassName() != "Product")){
                $classDefinition = $fieldValue->getClass();

                $fieldDefinitions = $classDefinition->getFieldDefinitions();

                foreach ($fieldDefinitions as $fieldDefinition) {
                    self::exportObjectField($productId, $fieldValue, $fieldDefinition, $relatedObject, $level +1);
                }
            }
        }else{
            Logger::warn("WARNING - exportRelationField - The export is defined only for objects");
        }
        
        return $relatedObject;
    }
    
    private static function exportLocalizedField(Localizedfield $fieldValue, Data $fieldDefinition){
        if(!($fieldDefinition instanceof Localizedfields)){
            throw new \Exception("ERROR - exportLocalizedField - Invalid type '".$fieldDefinition->getFieldtype()."'. Expected 'localizedfields'");
        }
        
        $fields = $fieldDefinition->getChildren();
        
        $localizedValues = array();
        
        $config = \Pimcore\Config::getSystemConfig();
        $validLanguages = explode(",",$config->general->validLanguages);
        
        foreach ($fields as $field) {
            $fieldName = $field->getName();
            
            foreach ($validLanguages as $lang) {
                $localizedValues[$fieldName][$lang] = $fieldValue->getLocalizedValue($fieldName, $lang);
            }
        }
        
        return $localizedValues;
    }
    
    private static function exportFieldcollection(int $productId, $level, Fieldcollection $fieldValue = null){
        $items = $fieldValue != null ? $fieldValue->getItems() : array();
        
        $fieldCollections = array();
        
        foreach ($items as $item) {
            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && $item instanceof FieldcollectionAbstractData && !($item instanceof ServerObjectInfo)){
                $fieldCollection = array();
                
                $definition = $item->getDefinition();
                foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                    self::exportObjectField($productId, $item, $fieldDefinition, $fieldCollection, $level + 1);
                }
                
                $fieldCollections[] = $fieldCollection;
            }
        }
        
        return $fieldCollections;
    }
    
     private static function exportObjectbrick(int $productId, $level, Objectbrick $fieldValue = null){
        $items = $fieldValue != null ? $fieldValue->getItems() : array();
        
        $objectBricks = array();
        
        foreach ($items as $item) {
            /**
             * Avoid circular dependency loop
             */
            if($level < 10 && $item instanceof ObjectbrickAbstractData ){
                $objectBrick = array();
                
                $definition = $item->getDefinition();
                foreach ($definition->getFieldDefinitions() as $fieldDefinition) {
                    self::exportObjectField($productId, $item, $fieldDefinition, $objectBrick, $level + 1);
                }
                
                $objectBricks[] = $objectBrick;
            }
        }
        
        return $objectBricks;
    }
    
    private static function exportImageField(Image $fieldValue){
        return array(
            "url" => BaseEcommerceConfig::getBaseUrl().urlencode_ignore_slash($fieldValue->getRelativeFileSystemPath())
        );
    }
    
    private static function exportExternalImageField(ExternalImage $fieldValue){
        return array(
            "url" => $fieldValue->getUrl()
        );
    }
    
    public static function exportHotspotImageField(Hotspotimage $fieldValue){
        $image = $fieldValue->getImage();
        
        return array(
            "url" => BaseEcommerceConfig::getBaseUrl().urlencode_ignore_slash($image->getRelativeFileSystemPath()),
            "crop" => $fieldValue->getCrop(),
            "hotspots" => $fieldValue->getHotspots(),
            "marker" => $fieldValue->getMarker()
        );
    }
    
    public static function exportImageGalleryField(ImageGallery $fieldValue){
        $images = array();
        
        foreach ($fieldValue->getItems() as $image) {
            $images[] = self::exportHotspotImageField($image);
        }
        
        return $images;
    }
    
    public static function exportVideoField(Video $fieldValue){
        $type = $fieldValue->getType();
        
        $data = $fieldValue->getData();
        
        $video = array(
            "type" => $type
        );
        
        if($data instanceof Asset){ 
            $video["url"] = BaseEcommerceConfig::getBaseUrl()."/var/assets".$data->getFullPath();
            $video["title"] = $fieldValue->getTitle();
            $video["description"] = $fieldValue->getDescription();
        }else{
            $video["id"] = $data;
        }
        
        return $video;
    }
}

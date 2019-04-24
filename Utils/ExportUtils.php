<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Logger;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Localizedfield;
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
     * @param Concrete|AbstractData $object
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
                $objectExport[$fieldName] = self::exportQuantityValueField($fieldValue);
                break;

            case "date":
                $objectExport[$fieldName] = date("Y-m-d", strtotime($fieldValue));
                break;

            case "select":
            case "multiselect":
                $objectExport[$fieldName] = self::exportSelectField($fieldValue, $fieldDefinition);
                break;

            case "rgbaColor":
                $objectExport[$fieldName] = self::exportRgbaColorField($fieldValue);
                break;

            case "manyToOneRelation":
                $objectExport[$fieldName] = self::exportRelationField($productId, $fieldValue, $level);
                break;

            case "manyToManyObjectRelation":
            case "advancedManyToManyObjectRelation":
                $objectExport[$fieldName] = self::exportMultipleRelationsField($productId, $fieldValue, $level);
                
                break;

            case "localizedfields":
                $objectExport[$fieldName] = self::exportLocalizedField($fieldValue, $fieldDefinition);
                break;

            case "fieldcollections":
                $objectExport[$fieldName] = self::exportFieldcollection($productId, $level, $fieldValue);
                break;
            
            case "image":
                $objectExport[$fieldName] = self::exportImageField($fieldValue);
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
        if(!($fieldDefinition instanceof Select || $fieldDefinition instanceof Multiselect)){
            throw new \Exception("ERROR - exportSelectField - Invalid type '".$fieldDefinition->getFieldtype()."'. Expected either 'select' or 'multiselect'");
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
            if($level < 10 && $item instanceof AbstractData && !($item instanceof ServerObjectInfo)){
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
    
    private static function exportImageField(Image $fieldValue){
        return array(
            "url" => BaseEcommerceConfig::getBaseUrl().$fieldValue->getRelativeFileSystemPath()
        );
    }
}

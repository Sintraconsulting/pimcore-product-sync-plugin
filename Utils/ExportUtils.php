<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Product;



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

    private static function exportObjectField(int $productId, Concrete $object, Data $fieldDefinition, array &$objectExport) {
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
                $objectExport[$fieldName] = self::exportRelationField($productId, $fieldValue);
                break;

            case "manyToManyObjectRelation":
            case "advancedManyToManyObjectRelation":
                $objectExport[$fieldName] = self::exportMultipleRelationsField($productId, $fieldValue);
                
                break;

            case "localizedfields":
                $objectExport[$fieldName] = self::exportLocalizedField($fieldValue, $fieldDefinition);
                break;

            case "fieldcollections":

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
                $values[] = $options[$option];
            }
        }else{
            $option = array_search($fieldValue, array_column($options, "value"));
            $values = $options[$option];
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
    private static function exportMultipleRelationsField(int $productId, $fieldValue){
        $relatedObjects = array();
        
        foreach ($fieldValue as $value) {
            $relatedObjects[] = self::exportRelationField($productId, $value);
        }
        
        return $relatedObjects;
    }
    
    private static function exportRelationField(int $productId, $fieldValue){
        if($fieldValue instanceof Concrete){
            $relatedObject = array(
                "id" => $fieldValue->getId(),
                "class" => $fieldValue->getClassName(),
                "created at" => date("Y-m-d H:i:s", $fieldValue->getCreationDate()),
                "modified at" => date("Y-m-d H:i:s", $fieldValue->getModificationDate())
            );

            if($fieldValue->getId() != $productId || $fieldValue->getClassName() != "Product"){
                $classDefinition = $fieldValue->getClass();

                $fieldDefinitions = $classDefinition->getFieldDefinitions();

                foreach ($fieldDefinitions as $fieldDefinition) {
                    self::exportObjectField($productId, $fieldValue, $fieldDefinition, $relatedObject);
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
}

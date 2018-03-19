<?php

namespace Magento2PimcoreBundle\Utils;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Product;

/**
 * Utils for mapping Pimcore Product Object to Magento2 Product Object
 *
 * @author Marco Guiducci
 */
class ProductUtils {

    private static $instance;

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function toMagento2Product(Product $product){
        
        $magento2Product = array();
        $magento2Product["attribute_set_id"] = 4;
        
        $extensionAttributes = array();
        $extensionAttributes["stock_item"] = array();
        $magento2Product["extension_attributes"] = $extensionAttributes;
        
        $magento2Product["custom_attributes"] = array();
        
        $fieldDefinitions = $product->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldname = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            
            $fieldValue = $product->getValueForFieldName($fieldname);
            
            switch ($fieldType) {
                case "quantityValue":
                    $this->insertSingleValue($magento2Product, $fieldname, $fieldValue->value);
                    break;
                
                case "numeric":
                    $this->insertSingleValue($magento2Product, $fieldname, $fieldValue);
                    break;
                
                case "localizedfields":
                    $localizedFields = $fieldValue->getItems();
                    if($localizedFields != null && count($localizedFields) > 0){
                        $this->insertLocalizedFields($magento2Product, $localizedFields);
                    }
                    break;
                
                case "objectbricks":
                    $objectBricks = $fieldValue->getItems();
                    if($objectBricks != null && count($objectBricks) > 0){
                        $this->insertObjectBricks($magento2Product, $objectBricks, $product->getClassId());
                    }
                    break;
                
                case "objects":
                    break;

                default:
                    $this->insertSingleValue($magento2Product, $fieldname, $fieldValue);
                    break;
            }
            
        }
        
        return $magento2Product;
        
    }
    
    private function insertSingleValue(&$magento2Product, $fieldname, $fieldvalue){
        if(strpos($fieldname, "stock_") === 0){
            $field = str_replace("stock_", "", $fieldname, $i=1);
            $magento2Product["extension_attributes"]["stock_item"][$field] = $fieldvalue;
            
        }else if(strpos($fieldname, "custom_") === 0){
            $field = str_replace("custom_", "", $fieldname, $i=1);
            $magento2Product["custom_attributes"][] = array(
                "attribute_code" => $field,
                "value" => $fieldvalue
            ); 
            
        } else{
            $magento2Product[$fieldname] = $fieldvalue;
        }
    }
    
    private function insertLocalizedFields(&$magento2Product, $localizedFields){
        foreach ($localizedFields["en"] as $fieldname => $fieldvalue) {
            $this->insertSingleValue($magento2Product, "custom_".$fieldname, $fieldvalue);
        }
    }
    
    private function insertObjectBricks(&$magento2Product, $objectBricks, $classId){
        foreach ($objectBricks as $objectBrick) {
            $type = $objectBrick->type;

            $db = Db::get();
            $brickfields = $db->fetchRow("SELECT * FROM object_brick_query_".$type."_".$classId);
            
            foreach ($brickfields as $fieldname => $fieldvalue) {
                if(!in_array($fieldname, array("o_id", "fieldname"))){
                    $this->insertSingleValue($magento2Product, "custom_".$fieldname, $fieldvalue);
                }
            }
        }
    }

}
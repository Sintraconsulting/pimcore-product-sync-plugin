<?php

namespace Magento2PimcoreBundle\Utils;

use Magento2PimcoreBundle\Utils\MagentoUtils;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Product;

/**
 * Utils for mapping Pimcore Product Object to Magento2 Product Object
 *
 * @author Marco Guiducci
 */
class ProductUtils extends MagentoUtils{

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
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            $fieldValue = $product->getValueForFieldName($fieldName);
            
            $this->mapField($magento2Product, $fieldName, $fieldType, $fieldValue, $product->getClassId());
        }
        
        return $magento2Product;
        
    }
    
    public function insertSingleValue(&$magento2Product, $fieldName, $fieldvalue){
        if(strpos($fieldName, "stock_") === 0){
            $field = str_replace("stock_", "", $fieldName, $i=1);
            $magento2Product["extension_attributes"]["stock_item"][$field] = $fieldvalue;
            
        }else if(strpos($fieldName, "custom_") === 0){
            $field = str_replace("custom_", "", $fieldName, $i=1);
            $magento2Product["custom_attributes"][] = array(
                "attribute_code" => $field,
                "value" => $fieldvalue
            ); 
            
        } else{
            $magento2Product[$fieldName] = $fieldvalue;
        }
    }

}
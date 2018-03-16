<?php

namespace Magento2PimcoreBundle\Utils;

use Pimcore\Logger;
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
        
        $magento2Product["sku"] = $product->sku;
        $magento2Product["name"] = $product->name;
        $magento2Product["price"] = floatval($product->getPrice()->value);
        $magento2Product["weight"] = floatval($product->weight);
        $magento2Product["status"] = $product->status;
        
        $magento2Product["attribute_set_id"] = 4;
        
        /**
         * Add Extension Attributes
         */
        
        $extensionAttributes = array();
        $extensionAttributes["stock_item"] = array(
            "qty" => $product->qty,
            "is_in_stock" => $product->is_in_stock,
        );
                
        $magento2Product["extension_attributes"] = $extensionAttributes;
        
        /**
         * Add custom fields
         * TO-DO - manage multi languages
         */
        $customAttributes = array();
        
        //localized fields
        $localizedFields = $product->getLocalizedfields();
        $items = $localizedFields->getItems();
        $fields = $items["en"];
        
        foreach ($fields as $fieldname => $fieldvalue) {
            if(!empty($fieldvalue)){
                $customAttributes[] = array(
                    "attribute_code" => $fieldname,
                    "value" => $fieldvalue
                );
            }
        }
        
        //categories
        $categoryIds = array();
        $categories = $product->getCategories();
        foreach ($categories as $category) {
            $categoryIds[] = $category->magentoid;
        }
        
        $customAttributes[] = array(
            "attribute_code" => "category_ids",
            "value" => $categoryIds
        );
        
        
//        //bricks
//        $attributes = $product->getAttributes();
//        foreach ($attributes as $attribute) {
//            Logger::debug(print_r($attribute,true));
//        }
        
        
        //other custom fields
        $customAttributes[] = array(
            "attribute_code" => "tax_class_id",
            "value" => $product->tax_class_id
        );
        
        $customAttributes[] = array(
            "attribute_code" => "color",
            "value" => $product->color
        );
        
        $magento2Product["custom_attributes"] = $customAttributes;
        
        return $magento2Product;
        
    }

}

<?php

namespace Magento2PimcoreBundle\Utils;

use Pimcore\Model\DataObject\Category;

/**
 * Utils for mapping Pimcore Category Object to Magento2 Category Object
 *
 * @author Marco Guiducci
 */
class CategoryUtils {

    private static $instance;

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function toMagento2Category(Category $category){
        $parentCategory = Category::getById($category->getParentId());
        
        $magento2Category = array();
        
        $magentoId = $category->magentoid;
        if($magentoId != null && !empty($magentoId)){
            $magento2Category["id"] = $magentoId;
        }
        
        $parentMagentoId = $parentCategory->magentoid;
        $magento2Category["parent_id"] = ($parentMagentoId != null && !empty($parentMagentoId)) ? $parentMagentoId : "";
        
        $magento2Category["name"] = $category->name;
        $magento2Category["is_active"] = $category->is_active;
        $magento2Category["include_in_menu"] = $category->include_in_menu;
        
        $customAttributes = array();

        $customAttributes[] = array(
            "attribute_code" => "display_mode",
            "value" => $category->display_mode
        );
        
        $customAttributes[] = array(
            "attribute_code" => "is_anchor",
            "value" => $category->is_anchor
        );
        
        /**
         * Add localized fields
         * TO-DO - manage multi languages
         */
        $localizedFields = $category->getLocalizedfields();
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
        
        $magento2Category["custom_attributes"] = $customAttributes;
        
        return $magento2Category;
        
    }

}

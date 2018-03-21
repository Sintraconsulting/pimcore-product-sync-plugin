<?php

namespace Magento2PimcoreBundle\Utils;

use Magento2PimcoreBundle\Utils\MagentoUtils;
use Pimcore\Model\DataObject\Category;

/**
 * Utils for mapping Pimcore Category Object to Magento2 Category Object
 *
 * @author Marco Guiducci
 */
class CategoryUtils extends MagentoUtils{

    private static $instance;
    
    private $configFile = __DIR__.'/config/category.json';

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function toMagento2Category(Category $category){
        $parentCategory = Category::getById($category->getParentId());
        
        $magento2Category = json_decode(file_get_contents($this->configFile), true);
        
        $magentoId = $category->magentoid;
        if($magentoId != null && !empty($magentoId)){
            $magento2Category["id"] = $magentoId;
        }
        
        $parentMagentoId = $parentCategory->magentoid;
        $magento2Category["parent_id"] = ($parentMagentoId != null && !empty($parentMagentoId)) ? $parentMagentoId : "";
        
        $fieldDefinitions = $category->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            
            if($fieldName != "magentoid"){
                $fieldType = $fieldDefinition->getFieldtype();
                $fieldValue = $category->getValueForFieldName($fieldName);

                $this->mapField($magento2Category, $fieldName, $fieldType, $fieldValue, $category->getClassId());
            }
            
        }
        
        return $magento2Category;
        
    }

}

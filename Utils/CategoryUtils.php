<?php

namespace Magento2PimcoreBundle\Utils;

use Magento2PimcoreBundle\ApiManager\CategoryAPIManager;
use Magento2PimcoreBundle\Utils\MagentoUtils;
use Pimcore\Logger;
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
    
    public function exportToMagento(Category $category){
        $apiManager = CategoryAPIManager::getInstance();

        $magento2Category = $this->toMagento2Category($category);

        Logger::debug("MAGENTO CATEGORY: ".json_encode($magento2Category));

        $magentoId = $category->getMagentoid();
        if($magentoId == null || empty($magentoId)){
            $result = $apiManager->createEntity($magento2Category);
            $category->setMagentoid($result["id"]);

        }else{
            $result = $apiManager->updateEntity($magentoId,$magento2Category);
        }

        Logger::debug("UPDATED CATEGORY: ".$result->__toString());
                
        $category->setMagento_syncronized(true);
        $category->setMagento_syncronyzed_at($result["updatedAt"]);

        $category->update(true);
    }
    
    private function toMagento2Category(Category $category){
        $parentCategory = Category::getById($category->getParentId(),true);
        
        $magento2Category = json_decode(file_get_contents($this->configFile), true);
        
        $magentoId = $category->magentoid;
        if($magentoId != null && !empty($magentoId)){
            $magento2Category["id"] = $magentoId;
        }else{
            unset($magento2Category["id"]);
        }
        
        $parentMagentoId = $parentCategory->magentoid;
        $magento2Category["parent_id"] = ($parentMagentoId != null && !empty($parentMagentoId)) ? $parentMagentoId : "1";
        
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

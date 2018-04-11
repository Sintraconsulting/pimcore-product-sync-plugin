<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Magento2PimcoreBundle\ApiManager\CategoryAPIManager;
use Magento2PimcoreBundle\Utils\CategoryUtils;

class Magento2PimcoreCategoryListener {

    public function onPostUpdate(Category $category) {
        
        $categoryLevel = $this->getCategoryLevel($category);
        $category->setLevel($categoryLevel);
        
        if($category->export_to_magento){
            $apiManager = CategoryAPIManager::getInstance();

            $categoryUtils = CategoryUtils::getInstance();
            $magento2Category = $categoryUtils->toMagento2Category($category);

            Logger::debug("MAGENTO CATEGORY: ".json_encode($magento2Category));

            $magentoId = $category->getMagentoid();
            if($magentoId == null || empty($magentoId)){
                $result = $apiManager->createEntity($magento2Category);
                $category->setMagentoid($result["id"]);

            }else{
                $result = $apiManager->updateEntity($magentoId,$magento2Category);
            }

            $category->setMagento_syncronized(true);
            $category->setMagento_syncronyzed_at($result["updatedAt"]);
        }
        
        $category->update(true);
        
        Logger::debug("UPDATED CATEGORY: ".$result->__toString());
    }

    public function onPostDelete(Category $category, $isUnpublished = false) {
        $apiManager = CategoryAPIManager::getInstance();
        
        $magentoId = $category->getMagentoid();
        if($magentoId != null && !empty($magentoId)){
            $magentoCategory = $apiManager->getEntityByKey($magentoId);

            if($magentoCategory["id"] == $magentoId){
                $apiManager->deleteEntity($magentoId);
            }
        }
        
        if($isUnpublished){
            $category->setMagento_syncronized(true);
            $category->setMagento_syncronyzed_at(date("Y-m-d H:i:s"));

            $category->update(true);
        }
    }
    
    private function getCategoryLevel(Category $category){
        $parentCategory = Category::getById($category->getParentId(), true);
        
        if($parentCategory != null){
            $parentLevel = $parentCategory->getLevel();
        
            if($parentLevel != null && !empty($parentLevel)){
                return 1 + intval($parentLevel);
            }
            
            return 1 + $this->getCategoryLevel($parentCategory);
        }else{
        
            return intval(0);
        }
    }

}

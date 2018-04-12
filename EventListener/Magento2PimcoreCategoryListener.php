<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Magento2PimcoreBundle\ApiManager\CategoryAPIManager;

class Magento2PimcoreCategoryListener {

    public function onPostUpdate(Category $category) {
        
        $categoryLevel = $this->getCategoryLevel($category);
        $category->setLevel($categoryLevel);
        
        /****** TO-DO: Manage Multi Languages ******/
        $config = \Pimcore\Config::getSystemConfig();
        $languages = explode(",",$config->general->validLanguages);
        $lang = $languages[0];
        
        $name = $category->getName();
        $category->setUrl_key(preg_replace('/\W+/', '-', strtolower($name)), $lang);
        
        $category->setMagento_syncronized(false);
        
        $category->update(true);
        
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

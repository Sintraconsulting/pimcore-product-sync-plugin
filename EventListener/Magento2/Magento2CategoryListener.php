<?php

namespace SintraPimcoreBundle\EventListener\Magento2;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\ApiManager\Mage2\CategoryAPIManager;
use SintraPimcoreBundle\EventListener\InterfaceListener;

class Magento2CategoryListener extends Magento2ObjectListener implements InterfaceListener{
    
    /**
     * @param Product $product
     */
    public function preAddAction($dataObject) {
        
    }
    
    /**
     * @param Category $category
     */
    public function preUpdateAction($category) {
        $this->setIsPublishedBeforeSave($category->isPublished());
    }

    /**
     * @param Category $category
     */
    public function postUpdateAction($category) {
        
        $categoryLevel = $this->getCategoryLevel($category);
        $category->setLevel($categoryLevel);
        
        /****** TO-DO: Manage Multi Languages ******/
        $config = \Pimcore\Config::getSystemConfig();
        $languages = explode(",",$config->general->validLanguages);
        $lang = $languages[0];
        
        $name = $category->getName();
        $category->setUrl_key(preg_replace('/\W+/', '-', strtolower($name)), $lang);
        
        $category->setMagento_sync(false);
        
        $category->update(true);
    }

    /**
     * @param Category $category
     */
    public function postDeleteAction($category, $isUnpublished = false) {
        
        $apiManager = CategoryAPIManager::getInstance();
        
        $magentoId = $category->getMagentoid();
        if($magentoId != null && !empty($magentoId)){
            $magentoCategory = $apiManager->getEntityByKey($magentoId);

            if($magentoCategory["id"] == $magentoId){
                $apiManager->deleteEntity($magentoId);
            }
        }
        
        if($isUnpublished){
            $category->setMagento_sync(true);
            $category->setMagento_sync_at(date("Y-m-d H:i:s"));

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

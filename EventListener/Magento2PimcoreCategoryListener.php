<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Magento2PimcoreBundle\ApiManager\CategoryAPIManager;
use Magento2PimcoreBundle\Utils\CategoryUtils;

class Magento2PimcoreCategoryListener {

    public function onPostAdd(Category $category) {
        
    }

    public function onPostUpdate(Category $category) {
        $apiManager = CategoryAPIManager::getInstance();
        
        $categoryUtils = CategoryUtils::getInstance();
        $magento2Category = $categoryUtils->toMagento2Category($category);
        
        Logger::debug("MAGENTO CATEGORY: ".json_encode($magento2Category));
        
        $magentoId = $category->getMagentoid();
        if($magentoId == null || empty($magentoId)){
            $result = $apiManager->createEntity($magento2Category);
            $category->setMagentoid($result["id"]);
            $category->update();
        }else{
            $result = $apiManager->updateEntity($magentoId,$magento2Category);
        }
        
        Logger::debug("UPDATED CATEGORY: ".$result->__toString());
    }

    public function onPostDelete(Category $category) {
        $apiManager = CategoryAPIManager::getInstance();
        
        $magentoId = $category->getMagentoid();
        $apiManager->deleteEntity($magentoId);
    }

}

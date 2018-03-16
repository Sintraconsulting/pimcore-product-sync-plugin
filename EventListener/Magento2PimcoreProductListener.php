<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use Magento2PimcoreBundle\ApiManager\ProductAPIManager;
use Magento2PimcoreBundle\Utils\ProductUtils;

class Magento2PimcoreProductListener {

    public function onPostAdd(Product $product) {
        
    }

    public function onPostUpdate(Product $product) {
        $apiManager = ProductAPIManager::getInstance();
        
        $productUtils = ProductUtils::getInstance();
        $magento2Product = $productUtils->toMagento2Product($product);
        
        Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));
        
        $sku = $product->getSku();
        $search = $apiManager->searchProducts("sku",$sku);
        
        if($search["totalCount"] === 0){
            $apiManager->createEntity($magento2Product);
        }else{
            $apiManager->updateEntity($sku,$magento2Product);
        }
        
    }

    public function onPostDelete(Product $product) {
        $apiManager = ProductAPIManager::getInstance();
        
        $sku = $product->getSku();
        $search = $apiManager->searchProducts("sku",$sku);
        
        if($search["totalCount"] > 0){
            $apiManager->deleteEntity($sku);
        }
    }

}

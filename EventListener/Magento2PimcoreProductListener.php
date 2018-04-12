<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use Magento2PimcoreBundle\ApiManager\ProductAPIManager;
use Magento2PimcoreBundle\Utils\ProductUtils;

class Magento2PimcoreProductListener {

    public function onPostUpdate(Product $product) {
        
        /****** TO-DO: Manage Multi Languages ******/
        $sku = $product->getSku();
        $name = $product->getName();
        $urlKey = ($sku == $name) ? $sku : $sku." ".$name;
        $product->setUrl_key(preg_replace('/\W+/', '-', strtolower($urlKey)));
        
        if($product->export_to_magento){
            $apiManager = ProductAPIManager::getInstance();

            $productUtils = ProductUtils::getInstance();
            $magento2Product = $productUtils->toMagento2Product($product);

            Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));

            $sku = $product->getSku();
            $search = $apiManager->searchProducts("sku",$sku);

            if($search["totalCount"] === 0){
                $result = $apiManager->createEntity($magento2Product);
            }else{
                $result = $apiManager->updateEntity($sku,$magento2Product);
            }

            $product->setMagento_syncronized(true);
            $product->setMagento_syncronyzed_at($result["updatedAt"]);
        }
        
        $product->update(true);
        
    }

    public function onPostDelete(Product $product, $isUnpublished = false) {
        $apiManager = ProductAPIManager::getInstance();
        
        $sku = $product->getSku();
        $search = $apiManager->searchProducts("sku",$sku);
        
        if($search["totalCount"] > 0){
            $apiManager->deleteEntity($sku);
        }
        
        if($isUnpublished){
            $product->setMagento_syncronized(true);
            $product->setMagento_syncronyzed_at(date("Y-m-d H:i:s"));

            $product->update(true);
        }
    }

}

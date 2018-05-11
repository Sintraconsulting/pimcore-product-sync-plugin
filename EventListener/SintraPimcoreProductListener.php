<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\ApiManager\ProductAPIManager;

class SintraPimcoreProductListener {

    public function onPostUpdate(Product $product) {
        
        /****** TO-DO: Manage Multi Languages ******/
        $config = \Pimcore\Config::getSystemConfig();
        $languages = explode(",",$config->general->validLanguages);
        $lang = $languages[0];
        
        $sku = $product->getSku();
        $name = $product->getName();
        $urlKey = ($sku == $name) ? $sku : $sku." ".$name;
        $product->setUrl_key(preg_replace('/\W+/', '-', strtolower($urlKey)), $lang);
        
        $product->setMagento_syncronized(false);
        
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

<?php

namespace SintraPimcoreBundle\EventListener\Magento2;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\EventListener\InterfaceListener;

class Magento2ProductListener extends Magento2ObjectListener implements InterfaceListener{
    
    /**
     * @param Product $product
     */
    public function preUpdateAction($product) {
        $this->setIsPublishedBeforeSave($product->isPublished());
    }

    /**
     * @param Product $product
     */
    public function postUpdateAction($product) {
        
        /****** TO-DO: Manage Multi Languages ******/
        $config = \Pimcore\Config::getSystemConfig();
        $languages = explode(",",$config->general->validLanguages);
        $lang = $languages[0];
        
        $sku = $product->getSku();
        $name = $product->getName();
        $urlKey = ($sku == $name) ? $sku : $name." ".$sku;
        $product->setUrl_key(preg_replace('/\W+/', '-', strtolower($urlKey)), $lang);
        
        $product->setMagento_sync(false);
        
        $product->update(true);
    }

    /**
     * @param Product $product
     */
    public function postDeleteAction($product, $isUnpublished = false) {
        
        $apiManager = ProductAPIManager::getInstance();
        
        $sku = $product->getSku();
        $search = $apiManager->searchProducts("sku",$sku);
        
        if($search["totalCount"] > 0){
            $apiManager->deleteEntity($sku);
        }
        
        if($isUnpublished){
            $product->setMagento_sync(true);
            $product->setMagento_sync_at(date("Y-m-d H:i:s"));

            $product->update(true);
        }
    }
    

}

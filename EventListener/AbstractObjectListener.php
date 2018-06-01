<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\Magento2\Magento2ObjectListener;
use SintraPimcoreBundle\EventListener\Shopify\ShopifyObjectListener;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

abstract class AbstractObjectListener {
    
    /**
     * @param Product|Category $dataObject
     */
    public abstract function preUpdateDispatcher($dataObject);
    
    /**
     * @param Product|Category $dataObject
     */
    public abstract function postUpdateDispatcher($dataObject, $saveVersionOnly);
    
    /**
     * @param Product|Category $dataObject
     */
    public abstract function postDeleteDispatcher($dataObject);
    

    public static function onPreUpdate (DataObjectEvent $e) {
       
        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();
            
            $enabledIntegrations = BaseEcommerceConfig::getEnabledIntegrations();
            
            if($enabledIntegrations["magento2"]){
                $magento2ObjectListener = new Magento2ObjectListener();
                $magento2ObjectListener->preUpdateDispatcher($obj);
            }
            
            if($enabledIntegrations["shopify"]){
                $shopifyObjectListener = new ShopifyObjectListener();
                $shopifyObjectListener->preUpdateDispatcher($obj);
            }
        }
    }
    
    public static function onPostUpdate (DataObjectEvent $e) {
        
        if ($e instanceof DataObjectEvent) {
            $saveVersionOnly = $e->hasArgument("saveVersionOnly");
            $obj = $e->getObject();
            
            $enabledIntegrations = BaseEcommerceConfig::getEnabledIntegrations();
            
            if($enabledIntegrations["magento2"]){
                $magento2ObjectListener = new Magento2ObjectListener();
                $magento2ObjectListener->postUpdateDispatcher($obj, $saveVersionOnly);
            }
            
            if($enabledIntegrations["shopify"]){
                $shopifyObjectListener = new ShopifyObjectListener();
                $shopifyObjectListener->postUpdateDispatcher($obj, $saveVersionOnly);
            }
        }
    }
    
    public static function onPostDelete (DataObjectEvent $e) {
       
        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();
            
            $enabledIntegrations = BaseEcommerceConfig::getEnabledIntegrations();
            
            if($enabledIntegrations["magento2"]){
                $magento2ObjectListener = new Magento2ObjectListener();
                $magento2ObjectListener->postDeleteDispatcher($obj);
            }
            
            if($enabledIntegrations["shopify"]){
                $shopifyObjectListener = new ShopifyObjectListener();
                $shopifyObjectListener->postDeleteDispatcher($obj);
            }
        }
    }

}
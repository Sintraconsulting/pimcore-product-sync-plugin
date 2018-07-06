<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\Magento2\Magento2ObjectListener;
use SintraPimcoreBundle\EventListener\Shopify\ShopifyObjectListener;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

use ReflectionClass;

abstract class AbstractObjectListener {
    
    /**
     * @param Product $dataObject
     */
    public abstract function postAddDispatcher($dataObject);
    
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
    
    public static function onPostAdd (DataObjectEvent $e) {
       
        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();
            
            $enabledIntegrations = BaseEcommerceConfig::getEnabledIntegrations();
            
            if($enabledIntegrations["magento2"]){
                $magento2ObjectListener = new Magento2ObjectListener();
                $magento2ObjectListener->postAddDispatcher($obj);
            }
            
            if($enabledIntegrations["shopify"]){
                $shopifyObjectListener = new ShopifyObjectListener();
                $shopifyObjectListener->postAddDispatcher($obj);
            }
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractObjectListener - Custom onPostAdd Event for namespace: ".$namespace);
                $customObjectListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\ObjectListener';
                
                if(class_exists($customObjectListenerClassName)){
                    $customObjectListenerClass = new ReflectionClass($customObjectListenerClassName);
                    $customObjectListener = $customObjectListenerClass->newInstance();
                    $customObjectListener->postAddDispatcher($obj);
                }else{
                    Logger::warn("AbstractObjectListener - WARNING. Class not found: ".$customObjectListenerClass);
                }
            }
        }
    }

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
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractObjectListener - Custom onPreUpdate Event for namespace: ".$namespace);
                $customObjectListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\ObjectListener';
                
                if(class_exists($customObjectListenerClassName)){
                    $customObjectListenerClass = new ReflectionClass($customObjectListenerClassName);
                    $customObjectListener = $customObjectListenerClass->newInstance();
                    $customObjectListener->preUpdateDispatcher($obj);
                }else{
                    Logger::warn("AbstractObjectListener - WARNING. Class not found: ".$customObjectListenerClass);
                }
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
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractObjectListener - Custom onPostUpdate Event for namespace: ".$namespace);
                $customObjectListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\ObjectListener';
                
                if(class_exists($customObjectListenerClassName)){
                    $customObjectListenerClass = new ReflectionClass($customObjectListenerClassName);
                    $customObjectListener = $customObjectListenerClass->newInstance();
                    $customObjectListener->postUpdateDispatcher($obj, $saveVersionOnly);
                }else{
                    Logger::warn("AbstractObjectListener - WARNING. Class not found: ".$customObjectListenerClass);
                }
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
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractObjectListener - Custom onPostDelete Event for namespace: ".$namespace);
                $customObjectListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\ObjectListener';
                
                if(class_exists($customObjectListenerClassName)){
                    $customObjectListenerClass = new ReflectionClass($customObjectListenerClassName);
                    $customObjectListener = $customObjectListenerClass->newInstance();
                    $customObjectListener->postDeleteDispatcher($obj);
                }else{
                    Logger::warn("AbstractObjectListener - WARNING. Class not found: ".$customObjectListenerClass);
                }
            }
        }
    }

}
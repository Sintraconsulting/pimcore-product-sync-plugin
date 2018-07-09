<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\Magento2\Magento2ObjectListener;
use SintraPimcoreBundle\EventListener\Shopify\ShopifyObjectListener;
use SintraPimcoreBundle\EventListener\General\ObjectListener;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

use ReflectionClass;

abstract class AbstractObjectListener {
    
    /**
     * @param Product $dataObject
     */
    public abstract function preAddDispatcher($dataObject);
    
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
    
    public static function onPreAdd (DataObjectEvent $e) {
       
        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();
            
            $objectListener = new ObjectListener();
            $objectListener->preAddDispatcher($obj);
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractObjectListener - Custom onPreAdd Event for namespace: ".$namespace);
                $customObjectListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\ObjectListener';
                
                if(class_exists($customObjectListenerClassName)){
                    $customObjectListenerClass = new ReflectionClass($customObjectListenerClassName);
                    $customObjectListener = $customObjectListenerClass->newInstance();
                    $customObjectListener->preAddDispatcher($obj);
                }else{
                    Logger::warn("AbstractObjectListener - WARNING. Class not found: ".$customObjectListenerClass);
                }
            }
        }
    }

    public static function onPreUpdate (DataObjectEvent $e) {
       
        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();
            
            $objectListener = new ObjectListener();
            $objectListener->preUpdateDispatcher($obj);
            
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
            
            $objectListener = new ObjectListener();
            $objectListener->postUpdateDispatcher($obj,$saveVersionOnly);
            
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
            
            $objectListener = new ObjectListener();
            $objectListener->postDeleteDispatcher($obj);
            
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
<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Logger;
use SintraPimcoreBundle\EventListener\Assets\AssetsListener;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

use ReflectionClass;

abstract class AbstractAssetsListener {
    
    /**
     * @param Asset $asset
     */
    public abstract function postAddDispatcher($asset);
    
    public static function onPostAdd (AssetEvent $e) {
       
        if ($e instanceof AssetEvent) {
            $asset = $e->getAsset();            
            
            $assetsListener = new AssetsListener();
            $assetsListener->postAddDispatcher($asset);
            
            $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
            $namespace = $customizationInfo["namespace"];
            
            if($namespace != null && !empty($namespace)){
                Logger::info("AbstractAssetsListener - Custom onPostAdd Event for namespace: ".$namespace);
                $customAssetsListenerClassName = '\\'.$namespace.'\\SintraPimcoreBundle\\EventListener\\Assets\\AssetsListener';
                
                if(class_exists($customAssetsListenerClassName)){
                    $customAssetsListenerClass = new ReflectionClass($customAssetsListenerClassName);
                    $customAssetsListener = $customAssetsListenerClass->newInstance();
                    $customAssetsListener->postAddDispatcher($asset);
                }else{
                    Logger::warn("AbstractAssetsListener - WARNING. Class not found: ".$customAssetsListenerClass);
                }
            }
        }
    }

}
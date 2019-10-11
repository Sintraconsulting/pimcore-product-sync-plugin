<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Logger;
use SintraPimcoreBundle\EventListener\Assets\AssetsListener;
use Pimcore\Model\DataObject\SintraPimcoreBundleConfiguration;
use ReflectionClass;

/**
 * Abstract class that invoke concrete listeners, passing the interested Asset
 * Provide dispatcher function that those listeners must implement in order to 
 * properly dispatch the request to the right listener, based on asset class.
 * 
 * @author Sintra Consulting
 */
abstract class AbstractAssetsListener {

    /**
     * Properly dispatch the request to the right listener 
     * after the 'postAdd' event is fired
     * 
     * @param @param Asset $asset
     */
    public abstract function postAddDispatcher($asset);

    /**
     * Invoke the dispatcher for the 'postAdd' event of the generic AssetsListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param AssetEvent $e the event
     */
    public static function onPostAdd(AssetEvent $e) {

        if ($e instanceof AssetEvent) {
            $asset = $e->getAsset();

            $assetsListener = new AssetsListener();
            $assetsListener->postAddDispatcher($asset);

            self::checkForCustomListener($asset, "onPostAdd");
        }
    }

    /**
     * Check for the existence of a custom event listener
     * and invoke the specific event dispatcher
     * 
     * @param Asset $asset
     * @param String $eventName
     */
    private static function checkForCustomListener($asset, $eventName) {
        $customNamespace = null;
        $configurationListing = new SintraPimcoreBundleConfiguration\Listing();
        
        if($configurationListing->getTotalCount() > 0){
            $config = $configurationListing->current();
            $customNamespace = $config->getCustomBundleNamespace();
        }

        if ($customNamespace != null && !empty($customNamespace)) {
            Logger::info("AbstractAssetsListener - Custom $eventName Event for namespace: " . $customNamespace);
            $customAssetsListenerClassName = '\\' . $customNamespace . '\\SintraPimcoreBundle\\EventListener\\Assets\\AssetsListener';

            if (class_exists($customAssetsListenerClassName)) {
                $customAssetsListenerClass = new ReflectionClass($customAssetsListenerClassName);
                $customAssetsListener = $customAssetsListenerClass->newInstance();
                $customAssetsListener->postAddDispatcher($asset);
            } else {
                Logger::warn("AbstractAssetsListener - WARNING. Class not found: " . $customAssetsListenerClass);
            }
        }
    }

}

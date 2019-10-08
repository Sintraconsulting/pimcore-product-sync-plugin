<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Services\InterfaceService;
use SintraPimcoreBundle\Controller\Sync\BaseSyncController;
use Pimcore\Model\DataObject\SintraPimcoreBundleConfiguration;

/**
 * Synchronizaton utils
 *
 * @author Sintra Consulting
 */
class SynchronizationUtils {

    /**
     * Get the synchronization service needed to synchronize an objects
     * based on server type and object class
     *
     * @param TargetServer $targetServer the server in which the object must be syncronized
     * @param String $class the object class
     * @return InterfaceService the synchronization service
     * @throws \ReflectionException
     */
    public static function getSynchronizationService(TargetServer $targetServer, $class) {
        $serverType = $targetServer->getServer_type();
        
        $customNamespace = null;
        $configurationListing = new SintraPimcoreBundleConfiguration\Listing();
        
        if($configurationListing->getTotalCount() > 0){
            $config = $configurationListing->current();
            $customNamespace = $config->getCustomBundleNamespace();
        }
        
        $serviceName = null;

        if ($customNamespace != null && !empty($customNamespace)) {
            $serviceName = $customNamespace . '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }

        if ($serviceName == null || !class_exists($serviceName)) {
            $serviceName = '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }

        $dataObjectServiceClass = new \ReflectionClass($serviceName);
        $dataObjectService = $dataObjectServiceClass->newInstanceWithoutConstructor();

        return $dataObjectService::getInstance();
    }

    /**
     * Get the base synchronization controller needed to synchronize objects.
     * Check of existance of an overriding controller in a custom repository
     * and return the original controller if not exists.
     *
     * @return BaseSyncController the base synchronization controller
     * @throws \ReflectionException
     */
    public static function getBaseSynchronizationController() {
        
        $customNamespace = null;
        $configurationListing = new SintraPimcoreBundleConfiguration\Listing();
        
        if($configurationListing->getTotalCount() > 0){
            $config = $configurationListing->current();
            $customNamespace = $config->getCustomBundleNamespace();
        }
        
        $controllerName = null;

        if ($customNamespace) {
            $controllerName = $customNamespace . '\SintraPimcoreBundle\Controller\Sync\CustomBaseSyncController';
        }

        $baseSyncController = null;
        if ($controllerName != null && class_exists($controllerName)) {
            $baseSyncControllerClass = new \ReflectionClass($controllerName);
            $baseSyncController = $baseSyncControllerClass->newInstance();
        } else {
            $baseSyncController = new BaseSyncController();
        }

        return $baseSyncController;
    }

    /**
     * Get the synchronization controller needed to synchronize an objects
     * based on server type.
     * Check of existance of an overriding controller in a custom repository
     * and return the original controller if not exists.
     *
     * @param TargetServer $targetServer the server in which the object must be syncronized
     * @return BaseSyncController the synchronization controller
     * @throws \ReflectionException
     */
    public static function getServerSynchronizationController(TargetServer $targetServer) {
        $serverType = $targetServer->getServer_type();

        $customNamespace = null;
        $configurationListing = new SintraPimcoreBundleConfiguration\Listing();
        
        if($configurationListing->getTotalCount() > 0){
            $config = $configurationListing->current();
            $customNamespace = $config->getCustomBundleNamespace();
        }
        
        $controllerName = null;

        if ($customNamespace) {
            $controllerName = $customNamespace . '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
        }else{
            $controllerName = '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
        }

        $syncController = null;
        if ($controllerName != null && class_exists($controllerName)) {
            $syncControllerClass = new \ReflectionClass($controllerName);
            $syncController = $syncControllerClass->newInstance();
        }

        /** @var BaseSyncController $syncController */
        return $syncController;
    }

}

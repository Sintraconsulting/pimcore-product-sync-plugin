<?php

namespace SintraPimcoreBundle\Utils;

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Services\InterfaceService;
use SintraPimcoreBundle\Controller\Sync\BaseSyncController;

class SynchronizationUtils {

    /**
     * Get the synchronization service needed to synchronize an objects
     * based on server type and object class
     * 
     * @param TargetServer $targetServer the server in which the object must be syncronized
     * @param String $class the object class
     * @return InterfaceService the synchronization service
     */
    public static function getSynchronizationService(TargetServer $targetServer, $class) {
        $serverType = $targetServer->getServer_type();

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $serviceName = null;

        if ($namespace) {
            $serviceName = $namespace . '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
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
     */
    public static function getBaseSynchronizationController() {
        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $controllerName = null;

        if ($namespace) {
            $controllerName = $namespace . '\SintraPimcoreBundle\Controller\Sync\CustomBaseSyncController';
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
     */
    public static function getServerSynchronizationController(TargetServer $targetServer) {
        $serverType = $targetServer->getServer_type();

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $controllerName = null;

        if ($namespace) {
            $controllerName = $namespace . '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
        }else{
            $controllerName = '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
        }

        $syncController = null;
        if ($controllerName != null && class_exists($controllerName)) {
            $syncControllerClass = new \ReflectionClass($controllerName);
            $syncController = $syncControllerClass->newInstance();
        }

        return $syncController;
    }

}

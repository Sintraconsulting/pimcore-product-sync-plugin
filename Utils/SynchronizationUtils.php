<?php

namespace SintraPimcoreBundle\Utils;

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Services\InterfaceService;

class SynchronizationUtils {
    
    /**
     * Get the synchronization service needed to synchronize an objects
     * based on server type and object class
     * 
     * @param TargetServer $targetServer the server in which the object must be syncronized
     * @param String $class the object class
     * @return InterfaceService the synchronization service
     */
    public static function getSynchronizationService(TargetServer $targetServer, $class){
        $serverType = $targetServer->getServer_type();

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $serviceName = null;
        
        if ($namespace) {
            $serviceName = $namespace . '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }
        
        if($serviceName == null || !class_exists($serviceName)){
            $serviceName = "\SintraPimcoreBundle\Services\\" . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }
        
        $dataObjectServiceClass = new \ReflectionClass($serviceName);
        $dataObjectService = $dataObjectServiceClass->newInstanceWithoutConstructor();
        
        return $dataObjectService::getInstance();
    }
}

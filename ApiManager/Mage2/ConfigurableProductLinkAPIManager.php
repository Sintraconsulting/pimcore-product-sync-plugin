<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use Pimcore\Model\DataObject\TargetServer;
use SpringImport\Swagger\Magento2\Client\Api\ConfigurableProductLinkManagementV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body108;
use Pimcore\Logger;

/**
 * Magento Rest Configurable Product Link API Manager 
 *
 * @author Marco Guiducci
 */
class ConfigurableProductLinkAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public static function addChildToProduct($sku, $childSku, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $configurableProductInstance = new ConfigurableProductLinkManagementV1Api($apiClient);
        
        try {
            $result = $configurableProductInstance->configurableProductLinkManagementV1AddChildPost($sku, new Body108(array("childSku" => $childSku)));
            return $result;
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public static function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'ConfigurableProductLinkAPIManager'");        
    }

    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ConfigurableProductLinkAPIManager'");
    }

    public static function getEntityByKey($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'getEntityByKey' not implemented in 'ConfigurableProductLinkAPIManager'");
    }

    public static function updateEntity($entityKey, $entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'updateEntity' not implemented in 'ConfigurableProductLinkAPIManager'");
    }

}

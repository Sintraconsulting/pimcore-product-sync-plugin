<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use Pimcore\Model\DataObject\TargetServer;
use SpringImport\Swagger\Magento2\Client\Api\ConfigurableProductLinkManagementV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body108;
use Pimcore\Logger;
use SpringImport\Swagger\Magento2\Client\ApiException;

/**
 * Magento Rest Configurable Product Link API Manager 
 *
 * @author Marco Guiducci
 */
class ConfigurableProductLinkAPIManager extends BaseMage2APIManager{
    
    public static function addChildToProduct($sku, $childSku, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $configurableProductInstance = new ConfigurableProductLinkManagementV1Api($apiClient);
        
        try {
            $result = $configurableProductInstance->configurableProductLinkManagementV1AddChildPost($sku, new Body108(array("childSku" => $childSku)));
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }

}

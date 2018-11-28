<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use Pimcore\Model\DataObject\TargetServer;
use SpringImport\Swagger\Magento2\Client\Api\ConfigurableProductLinkManagementV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body108;
use Pimcore\Logger;
use SpringImport\Swagger\Magento2\Client\ApiException;

/**
 * Configurable Product Link API Manager for Magento2
 *
 * @author Sintra Consulting
 */
class ConfigurableProductLinkAPIManager extends BaseMage2APIManager{
    
    /**
     * Attach a child to a product in order to create a configurable product
     * Instantiate the API Client and perform the call.
     * Return false if the API call fails.
     * 
     * @param String $sku the parent SKU
     * @param String $childSku the child SKU
     * @param TargetServer $server the server in which the products are
     * @return mixed the API call response
     */
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

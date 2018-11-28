<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\Api\CatalogProductAttributeRepositoryV1Api;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use SpringImport\Swagger\Magento2\Client\ApiException;

/**
 * Product Attributes API Manager for Magento2
 *
 * @author Sintra Consulting
 */
class ProductAttributesAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public static function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    /**
     * Get a product attribute by key.
     * Instantiate the API Client and perform the call.
     * Return false if the API call fails.
     * 
     * @param String $entityKey the attribute key
     * @param TargetServer $server the server in which the attribute is
     * @return mixed the API call result
     */
    public static function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = self::getApiInstance($server);
        
        $productAttributesInstance = new CatalogProductAttributeRepositoryV1Api($apiClient);
        
        try {
            $result = $productAttributesInstance->catalogProductAttributeRepositoryV1GetGet($entityKey);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }

    public static function updateEntity($entityKey, $entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'updateEntity' not implemented in 'ProductAttributesAPIManager'");
    }

}

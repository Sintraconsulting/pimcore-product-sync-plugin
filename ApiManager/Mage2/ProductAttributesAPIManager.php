<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\Api\CatalogProductAttributeRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\CatalogDataProductAttributeInterface;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use SpringImport\Swagger\Magento2\Client\ApiException;

/**
 * Magento Rest Product Attributes API Manager 
 *
 * @author Utente
 */
class ProductAttributesAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public static function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    /**
     * 
     * @param type $entityKey
     * @param TargetServer $server
     * @return CatalogDataProductAttributeInterface|boolean
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

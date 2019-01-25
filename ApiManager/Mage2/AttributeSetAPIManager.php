<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\Api\CatalogAttributeSetRepositoryV1Api;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use SpringImport\Swagger\Magento2\Client\ApiException;
use Pimcore\Logger;

/**
 * Attribute Set API Manager for Magento2
 *
 * @author Sintra Consulting
 */
class AttributeSetAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public static function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'AttributeSetAPIManager'");
    }

    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'AttributeSetAPIManager'");
    }
    
    /**
     * Get all existent attribute sets
     * Instantiate the API Client and perform the call for getting the attribute sets.
     * Return false if the API call fails.
     * 
     * @param TargetServer $server the server in which the attribute sets are.
     * @return mixed The API call response.
     */
    public static function getAllAttributeSet(TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet(null,null,null,"attribute_set_name","asc");
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    public static function getDefaultAttributeSet(TargetServer $server){
        return $this->searchAttributeSet($server, "attribute_set_name","Default","eq");
    }

    /**
     * Get an existent attribute set.
     * Instantiate the API Client and perform the call for getting the attribute set.
     * Return false if the API call fails.
     * 
     * 
     * @param mixed $entityKey the key of the attribute set to get.
     * @param TargetServer $server the server in which the attribute set is.
     * @return mixed The API call response.
     */
    public static function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = self::getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetGet($entityKey);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    /**
     * Search for existent Attribute sets.
     * Instantiate the API Client and perform the call for search.
     * Return false if the API call fails.
     */
    public static function searchAttributeSet(TargetServer $server, $field, $value, $conditionType = null){
        $apiClient = self::getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet($field, $value, $conditionType = null);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }

    public function updateEntity($entityKey, $entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'updateEntity' not implemented in 'AttributeSetAPIManager'");
    }

}

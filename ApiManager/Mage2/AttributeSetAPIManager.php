<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\Api\CatalogAttributeSetRepositoryV1Api;
use Pimcore\Model\DataObject\TargetServer;

/**
 * Magento Rest Attribute Set API Manager 
 *
 * @author Marco Guiducci
 */
class AttributeSetAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'AttributeSetAPIManager'");
    }

    public function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'AttributeSetAPIManager'");
    }
    
    public function getAllAttributeSet(TargetServer $server){
        $apiClient = $this->getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet(null,null,null,"attribute_set_name","asc");
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public function getDefaultAttributeSet(TargetServer $server){
        return $this->searchAttributeSet($server, "attribute_set_name","Default","eq");
    }

    public function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetGet($entityKey);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public function searchAttributeSet(TargetServer $server, $field, $value, $conditionType = null){
        $apiClient = $this->getApiInstance($server);
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet($field, $value, $conditionType = null);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }

    public function updateEntity($entityKey, $entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'updateEntity' not implemented in 'AttributeSetAPIManager'");
    }

}

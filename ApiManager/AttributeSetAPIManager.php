<?php

namespace SintraPimcoreBundle\ApiManager;

use SpringImport\Swagger\Magento2\Client\Api\CatalogAttributeSetRepositoryV1Api;

/**
 * Magento Rest Attribute Set API Manager 
 *
 * @author Marco Guiducci
 */
class AttributeSetAPIManager extends AbstractAPIManager{
    
    private static $instance;

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createEntity($entity) {
        
    }

    public function deleteEntity($entityKey) {
        
    }
    
    public function getAllAttributeSet(){
        $apiClient = $this->getMagento2ApiInstance();
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet(null,null,null,"attribute_set_name","asc");
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public function getDefaultAttributeSet(){
        return $this->searchAttributeSet("attribute_set_name","Default","eq");
    }

    public function getEntityByKey($entityKey) {
        $apiClient = $this->getMagento2ApiInstance();
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetGet($entityKey);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public function searchAttributeSet($field, $value, $conditionType = null){
        $apiClient = $this->getMagento2ApiInstance();
        
        $attributeSetInstance = new CatalogAttributeSetRepositoryV1Api($apiClient);
        
        try {
            $result = $attributeSetInstance->catalogAttributeSetRepositoryV1GetListGet($field, $value, $conditionType = null);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }

    public function updateEntity($entityKey, $entity) {
        
    }

}

<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\Api\CatalogProductAttributeRepositoryV1Api;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;

/**
 * Magento Rest Product Attributes API Manager 
 *
 * @author Utente
 */
class ProductAttributesAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public function createEntity($entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'createEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    public function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ProductAttributesAPIManager'");
    }

    public function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        
        $productAttributesInstance = new CatalogProductAttributeRepositoryV1Api($apiClient);
        
        try {
            $result = $productAttributesInstance->catalogProductAttributeRepositoryV1GetGet($entityKey);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }

    public function updateEntity($entityKey, $entity, TargetServer $server) {
        throw new \Exception("ERROR - Method 'updateEntity' not implemented in 'ProductAttributesAPIManager'");
    }

}

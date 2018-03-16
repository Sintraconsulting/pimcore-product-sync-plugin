<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Magento2PimcoreBundle\ApiManager;

use Pimcore\Logger;
use SpringImport\Swagger\Magento2\Client\Api\CatalogProductRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body18;

//include_once 'vendor/springimport/swagger-magento2-client/lib/Api/CatalogProductRepositoryV1Api.php';
//include_once 'vendor/springimport/swagger-magento2-client/lib/Model/Body18.php';
//include_once 'AbstractAPIManager.php';

/**
 * Magento Rest Product API Manager 
 *
 * @author Marco Guiducci
 */
class ProductAPIManager extends AbstractAPIManager {
    
    private static $instance;

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createEntity($entity) {
        $apiClient = $this->getApiInstance();

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $product = array("product" => $entity, "saveOptions" => true);
            $productBody = new Body18($product);
            
            $result = $productInstance->catalogProductRepositoryV1SavePost($productBody);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
        }
    }
    
    public function deleteEntity($sku) {
        $apiClient = $this->getApiInstance();

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1DeleteByIdDelete($sku);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
        }
    }
    
    public function getEntityByKey($sku) {
        return $this->getEntity($sku,null,null,null);
    }

    public function getEntity($sku, $editMode = null, $storeId = null, $forceReload = null) {
        $apiClient = $this->getApiInstance();

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1GetGet($sku, $editMode, $storeId, $forceReload);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    /**
     * Search Product with search condition
     * @param $field field used for research
     * @param $value the field value 
     * @param $conditionType condition on the field value. Available conditions:
     * - eq:         Equals.
     * - finset:     A value within a set of values
     * - gt:         Greater than
     * - gteq:       Greater than or equal
     * - in:         In. The value can contain a comma-separated list of values.
     * - like:       Like. The value can contain the SQL wildcard characters when like is specified.
     * - lt:         Less than
     * - lteq:       Less than or equal
     * - moreq:      More or equal
     * - neq:        Not equal
     * - nin:        Not in. The value can contain a comma-separated list of values.
     * - notnull:    Not null
     * - null:       Null
     */
    public function searchProducts($field, $value, $conditionType = null) {
        $apiClient = $this->getApiInstance();

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1GetListGet($field, $value, $conditionType);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public function updateEntity($sku, $entity) {
        $apiClient = $this->getApiInstance();

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $product = array("product" => $entity, "saveOptions" => true);
            $productBody = new Body18($product);
            
            $result = $productInstance->catalogProductRepositoryV1SavePut($sku, $productBody);
            return $result;
        } catch (Exception $e) {
            Logger::err($e->getMessage());
        }
    }
}

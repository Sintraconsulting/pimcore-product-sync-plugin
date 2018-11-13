<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use Pimcore\Logger;
use SpringImport\Swagger\Magento2\Client\Api\CatalogProductRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body18;
use \SpringImport\Swagger\Magento2\Client\ApiException;

use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;

/**
 * Magento2 Rest Product API Manager 
 *
 * @author Marco Guiducci
 */
class Mage2ProductAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    public static function createEntity($entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $product = array("product" => $entity, "saveOptions" => true);
            $productBody = new Body18($product);
            
            $result = $productInstance->catalogProductRepositoryV1SavePost($productBody);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }
    
    public static function deleteEntity($sku, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1DeleteByIdDelete($sku);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }
    
    public static function getEntityByKey($sku, TargetServer $server) {
        return $this->getEntity($server,$sku,null,null,null);
    }

    public static function getEntity(TargetServer $server, $sku, $editMode = null, $storeId = null, $forceReload = null) {
        $apiClient = self::getApiInstance($server);

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1GetGet($sku, $editMode, $storeId, $forceReload);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    /**
     * Search Product with search condition
     * @param string $field field used for research
     * @param string $value the field value
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
    public static function searchProducts(TargetServer $server, $field, $value, $conditionType = null) {
        $apiClient = self::getApiInstance($server);

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $result = $productInstance->catalogProductRepositoryV1GetListGet($field, $value, $conditionType);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    public static function updateEntity($sku, $entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        $productInstance = new CatalogProductRepositoryV1Api($apiClient);

        try {
            $product = array("product" => $entity, "saveOptions" => true);
            $productBody = new Body18($product);
            
            $result = $productInstance->catalogProductRepositoryV1SavePut($sku, $productBody);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }

}

<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use Pimcore\Logger;
use SpringImport\Swagger\Magento2\Client\Api\CatalogProductRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body18;
use \SpringImport\Swagger\Magento2\Client\ApiException;

use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;

/**
 * Product API Manager for Magento2
 *
 * @author Sintra Consulting
 */
class Mage2ProductAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    /**
     * Create a new product.
     * Instantiate the API Client and perform the call for creation.
     * Throw an exception if the API call fails.
     * 
     * @param mixed $entity the product to create. Will be used in the API call body.
     * @param TargetServer $server the server in which the product should be created.
     * @return mixed The API call response.
     */
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
    
    /**
     * Delete an existent product.
     * Instantiate the API Client and perform the call for deletion.
     * Throw an exception if the API call fails.
     * 
     * @param mixed $sku the SKU of the product to delete.
     * @param TargetServer $server the server in which the product should be deleted.
     * @return mixed The API call response.
     */
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
    
    /**
     * Get an existent product by $sku.
     * 
     * @param mixed $sku the $sku of the entity.
     * @param TargetServer $server the server in which the product is.
     * @return mixed The API call response.
     */
    public static function getEntityByKey($sku, TargetServer $server) {
        return $this->getEntity($server,$sku,null,null,null);
    }

    /**
     * Get an existent product by $sku.
     * Instantiate the API Client and perform the call for getting the product.
     * Return false if the API call fails.
     * 
     * @param TargetServer $server the server in which the product is.
     * @param mixed $sku the SKU of the product to get.
     * @return mixed The API call response.
     */
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
     * Search for existent products.
     * Instantiate the API Client and perform the call for search.
     * 
     * Search Product with search condition
     * @param TargetServer $server the server in which the products are.
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
    
    /**
     * Update an existent product
     * Instantiate the API Client and perform the call fore update
     * Throw an exception if the API call fails.
     * 
     * @param mixed $sku the sku of the product to update.
     * @param mixed $entity the product to update. Will be used in the API call body
     * @param TargetServer $server the server in which the product should be updated
     * @return mixed The API call response.
     */
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

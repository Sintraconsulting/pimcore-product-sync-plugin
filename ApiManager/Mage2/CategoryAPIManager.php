<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\ApiException;
use SpringImport\Swagger\Magento2\Client\Api\CatalogCategoryRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body30;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use Pimcore\Logger;
/**
 * Category API Manager for Magento2
 *
 * @author Sintra Consulting
 */
class CategoryAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
    /**
     * Create a new category.
     * Instantiate the API Client and perform the call for creation.
     * Throw an exception if the API call fails.
     * 
     * @param mixed $entity the category to create. Will be used in the API call body.
     * @param TargetServer $server the server in which the category should be created.
     * @return mixed The API call response.
     */
    public static function createEntity($entity, TargetServer $server) {
        
        $apiClient = self::getApiInstance($server);
        
        $categoryInstance = new CatalogCategoryRepositoryV1Api($apiClient);
        
        try {
            $category = array("category" => $entity);
            $categoryBody = new Body30($category);
            $result = $categoryInstance->catalogCategoryRepositoryV1SavePost($categoryBody);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }

    /**
     * Delete an existent category.
     * Instantiate the API Client and perform the call for deletion.
     * Throw an exception if the API call fails.
     * 
     * @param mixed $categoryId the id of the category to delete.
     * @param TargetServer $server the server in which the category should be deleted.
     * @return mixed The API call response.
     */
    public static function deleteEntity($categoryId, TargetServer $server) {
        $apiClient = self::getApiInstance($server);
        
        $categoryInstance = new CatalogCategoryRepositoryV1Api($apiClient);
        
        try {
            $result = $categoryInstance->catalogCategoryRepositoryV1DeleteByIdentifierDelete($categoryId);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }

    /**
     * Get an existent category by id.
     * 
     * @param mixed $categoryId the id of the entity.
     * @param TargetServer $server the server in which the category is.
     * @return mixed The API call response.
     */
    public static function getEntityByKey($categoryId, TargetServer $server) {
        return $this->getEntity($server, $categoryId);
    }
    
    /**
     * Get an existent category by id.
     * Instantiate the API Client and perform the call for getting the category.
     * Return false if the API call fails.
     * 
     * @param TargetServer $server the server in which the category is.
     * @param mixed $categoryId the id of the category to get.
     * @return mixed The API call response.
     */
    public function getEntity(TargetServer $server, $categoryId, $storeId = null) {
        $apiClient = self::getApiInstance($server);
        
        $categoryInstance = new CatalogCategoryRepositoryV1Api($apiClient);
        
        try {
            $result = $categoryInstance->catalogCategoryRepositoryV1GetGet($categoryId, $storeId);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }

    /**
     * Update an existent category
     * Instantiate the API Client and perform the call fore update
     * Throw an exception if the API call fails.
     * 
     * @param mixed $categoryId the id of the category to update.
     * @param mixed $entity the category to update. Will be used in the API call body
     * @param TargetServer $server the server in which the category should be updated
     * @return mixed The API call response.
     */
    public static function updateEntity($categoryId, $entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);
        
        $categoryInstance = new CatalogCategoryRepositoryV1Api($apiClient);
        
        try {
            $category = array("category" => $entity);
            $categoryBody = new Body30($category);
            
            $result = $categoryInstance->catalogCategoryRepositoryV1SavePut($categoryId, $categoryBody);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            throw new \Exception($e->getResponseBody()->message);
        }
    }

}

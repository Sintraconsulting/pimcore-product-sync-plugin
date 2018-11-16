<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use \SpringImport\Swagger\Magento2\Client\ApiException;
use SpringImport\Swagger\Magento2\Client\Api\CatalogCategoryRepositoryV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body30;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
/**
 * Magento Rest Category API Manager 
 *
 * @author Marco Guiducci
 */
class CategoryAPIManager extends BaseMage2APIManager implements APIManagerInterface{
    
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

    public static function getEntityByKey($categoryId, TargetServer $server) {
        return $this->getEntity($server, $categoryId);
    }
    
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

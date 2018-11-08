<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\ApiException;
use Pimcore\Model\DataObject\TargetServer;
use SpringImport\Swagger\Magento2\Client\Api\CatalogProductAttributeMediaGalleryManagementV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body29;
use Pimcore\Logger;

class ProductAttributeMediaGalleryAPIManager extends BaseMage2APIManager{
    
    public static function getAllProductEntries($sku, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1GetListGet($sku);
            return $result;
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public static function getProductEntry($sku, $entryId, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1GetGet($sku, $entryId);
            return $result;
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
    
    public static function addEntryToProduct($sku, $entry, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1CreatePost($sku, new Body29(array("entry" => $entry)));
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return array("ApiException" => $e->getResponseBody()->message);
        }
    }
    
    public static function updateProductEntry($sku, $entryId, $entry, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1UpdatePut($sku, $entryId, new Body29(array("entry" => $entry)));
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return array("ApiException" => $e->getResponseBody()->message);
        }
    }
    
    public static function deleteProductEntry($sku, $entryId, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1RemoveDelete($sku, $entryId);
            return $result;
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
            return false;
        }
    }
}

<?php

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SpringImport\Swagger\Magento2\Client\ApiException;
use Pimcore\Model\DataObject\TargetServer;
use SpringImport\Swagger\Magento2\Client\Api\CatalogProductAttributeMediaGalleryManagementV1Api;
use SpringImport\Swagger\Magento2\Client\Model\Body29;
use Pimcore\Logger;

/**
 * Product Attribute Media Gallery API Manager for Magento2
 * 
 * @author Sintra Consulting
 */
class ProductAttributeMediaGalleryAPIManager extends BaseMage2APIManager{
    
    /**
     * Get all media entries of a product
     * Instantiate the API Client and perform the call.
     * Return false if the API call fails.
     * 
     * @param type $sku the product SKU
     * @param TargetServer $server the server in which the product is
     * @return mixed the API call response
     */
    public static function getAllProductEntries($sku, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1GetListGet($sku);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    /**
     * Get a media entries of a product
     * Instantiate the API Client and perform the call.
     * Return false if the API call fails.
     * 
     * @param String $sku the product SKU
     * @param int $entryId the media entry id
     * @param TargetServer $server the server in which the product is
     * @return mixed the API call response
     */
    public static function getProductEntry($sku, $entryId, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1GetGet($sku, $entryId);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
    
    /**
     * Add a media entries of a product
     * Instantiate the API Client and perform the call.
     * Throw an exception if the API call fails.
     * 
     * @param String $sku the product SKU
     * @param mixed $entry the media entry
     * @param TargetServer $server the server in which the product is
     * @return mixed the API call response
     */
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
    
    /**
     * Update a media entries of a product
     * Instantiate the API Client and perform the call.
     * Throw an exception if the API call fails.
     * 
     * @param String $sku the product SKU
     * @param int $entryId the media entry id
     * @param mixed $entry the media entry
     * @param TargetServer $server the server in which the product is
     * @return mixed the API call response
     */
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
    
    /**
     * Delete a media entries of a product
     * Instantiate the API Client and perform the call.
     * Return false if the API call fails.
     * 
     * @param String $sku the product SKU
     * @param int $entryId the media entry id
     * @param TargetServer $server the server in which the product is
     * @return mixed the API call response
     */
    public static function deleteProductEntry($sku, $entryId, TargetServer $server){
        $apiClient = self::getApiInstance($server);
        
        $productAttributeMediaGalleryInstance = new CatalogProductAttributeMediaGalleryManagementV1Api($apiClient);
        
        try {
            $result = $productAttributeMediaGalleryInstance->catalogProductAttributeMediaGalleryManagementV1RemoveDelete($sku, $entryId);
            return $result;
        } catch (ApiException $e) {
            Logger::err($e->getResponseBody()->message);
            return false;
        }
    }
}

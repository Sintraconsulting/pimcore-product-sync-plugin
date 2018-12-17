<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Shopify;

use Pimcore\Logger;
use Pimcore\Tool\RestClient\Exception;
use SintraPimcoreBundle\ApiManager\APIManagerInterface;
use Pimcore\Model\DataObject\TargetServer;

/**
 * Product API Manager for Shopify
 *
 * @author Sintra Consulting
 */
class ShopifyProductAPIManager extends BaseShopifyAPIManager implements APIManagerInterface{

    /**
     * Get an existent product by key.
     * Instantiate the API Client and perform the call for getting the product.
     * Return false if the API call fails.
     *
     * @param mixed $entityKey the key of the product to get.
     * @param TargetServer $server the server in which the product is.
     * @return mixed The API call response.
     */
    public static function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        try {
            $result = $apiClient->Product($entityKey)->get();
            return $result;
        } catch (Exception $e) {
            Logger::err('SEARCH SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }

    /**
     * Search for products.
     * Instantiate the API Client and perform the call for getting the products.
     * Return false if the API call fails.
     *
     * @param mixed $filters a filtering condition.
     * @param TargetServer $server the server in which the products are.
     * @return mixed The API call response.
     */
    public static function searchShopifyProducts ($filters, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        try {
            $result = $apiClient->Product->get($filters);
            return $result;
        } catch (Exception $e) {
            Logger::err('SEARCH SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new product.
     * Instantiate the API Client and perform the call for creation.
     * Return false if the API call fails.
     *
     * @param mixed $entity the product to create. Will be used in the API call body.
     * @param TargetServer $server the server in which the product should be created.
     * @return mixed The API call response.
     */
    public static function createEntity($entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        try {
            $result = $apiClient->Product->post($entity);
            return $result;
        } catch (Exception $e) {
            Logger::err('CREATE SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existent product.
     * Instantiate the API Client and perform the call for update.
     * Return false if the API call fails.
     *
     * @param mixed $entityKey the key of the product to get.
     * @param mixed $entity the product to create. Will be used in the API call body.
     * @param TargetServer $server the server in which the product should be updated.
     * @return mixed The API call response.
     */
    public static function updateEntity($entityKey, $entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        try {
            $result = $apiClient->Product($entityKey)->put($entity);
            return $result;
        } catch (Exception $e) {
            Logger::err('UPDATE SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }

    /**
     * Not used for this implementation.
     * @param mixed $entityKey
     * @param TargetServer $server
     * @throws \Exception
     */
    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ShopifyProductAPIManager'");
    }

    /**
     * Get inventory information based on the filters
     * Instantiate the API Client and perform the call for get.
     * Return false if the API call fails.
     *
     * @param array $filters - associative array with supported filters as key.
     * @param TargetServer $server - the server from which the information should be fetched.
     * @return bool|array
     */
    public function getInventoryInfo ($filters, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->InventoryLevel->get($filters);
            return $result;
        } catch (\Exception $e) {
            Logger::err('GET SHOPIFY INVENTORY LEVELS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set inventory information based on the payload
     * Instantiate the API Client and perform the call for update.
     * Return false if the API call fails.
     *
     * @param array $payload - associative array containing information to be changed for the specific inventory
     * @param TargetServer $server - the server from which the information should be fetched.
     * @return bool|array - json object converted to associative array
     */
    public function updateInventoryInfo ($payload, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->InventoryLevel->post($payload, $apiClient->InventoryLevel->generateUrl([], 'set'), false);
            return $result;
        } catch (\Exception $e) {
            Logger::err('UPDATE SHOPIFY INVENTORY LEVELS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get general product metafields
     * Instantiate the API Client and perform the call for fetching.
     * Return false if the API call fails.
     *
     * @param string $productId - id of the shopify Product
     * @param TargetServer $server - the server from which the information should be fetched.
     * @return bool|array - json array converted to associative array
     */
    public function getProductMetafields ($productId, TargetServer $server) : array {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield->get();
            return $result;
        } catch (\Exception $e) {
            Logger::err('GET SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create Product Metafield
     * Instantiate the API Client and perform the call for creation.
     * Return false if the API call fails.
     *
     * @param int $payload - array with shopify's Product Metafield
     * @param string $productId - id of shopify's Product
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function createProductMetafield ($payload, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield->post($payload);
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update Product Metafield
     * Instantiate the API Client and perform the call for update.
     * Return false if the API call fails.
     *
     * @param int $payload - array with shopify's Product Metafield information to be changed
     * @param string $productId - id of shopify's Product Metafield
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function updateProductMetafield ($payload, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            # Remove unnecessary fields from the payload
            unset($payload['namespace']);
            unset($payload['key']);
            $result = $apiClient->Product($productId)->Metafield($payload['id'])->put($payload);
            return $result;
        } catch (\Exception $e) {
            Logger::err('UPDATE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete Product Metafield
     * Instantiate the API Client and perform the call for deletion.
     * Return false if the API call fails.
     *
     * @param int $metafieldId - id of shopify's Metafield
     * @param string $productId - id of shopify's Product
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function deleteProductMetafield ($metafieldId, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield($metafieldId)->delete();
            return $result;
        } catch (\Exception $e) {
            Logger::err('DELETE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get ProductVariant metafields
     * Instantiate the API Client and perform the call for fetching.
     * Return false if the API call fails.
     *
     * @param string $productId - id of shopify's Product
     * @param string $varId - id of shopify's ProductVariant
     * @param TargetServer $server - the server from which the information should be fetched.
     * @return bool|array - json array converted to associative array
     */
    public function getProductVariantMetafields ($productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield->get();
            return $result;
        } catch (\Exception $e) {
            Logger::err('GET SHOPIFY VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create ProductVariant Metafield
     * Instantiate the API Client and perform the call for creation.
     * Return false if the API call fails.
     *
     * @param int $payload - array with shopify's ProductVariation Metafield
     * @param $productId - id of shopify's Product
     * @param $varId - id of shopify's ProductVariant
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function createProductVariantMetafield ($payload, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield->post($payload);
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update ProductVariant Metafield
     * Instantiate the API Client and perform the call for update.
     * Return false if the API call fails.
     *
     * @param int $payload - array with information to update the metafield
     * @param string $productId - id of shopify's Product
     * @param string $varId - id of shopify's ProductVariant Metafield
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function updateProductVariantMetafield ($payload, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            # Remove unnecessary fields from the payload
            unset($payload['namespace']);
            unset($payload['key']);
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield($payload['id'])->put($payload);
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete ProductVariant Metafield
     * Instantiate the API Client and perform the call for deletion.
     * Return false if the API call fails.
     *
     * @param int $metafieldId - id of shopify's Metafield
     * @param string $productId - id of shopify's Product
     * @param string $varId - id of shopify's ProductVariant
     * @param TargetServer $server - the server where the information should be put.
     * @return bool|array - json object converted to associative array
     */
    public function deleteProductVariantMetafield ($metafieldId, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield($metafieldId)->delete();
            return $result;
        } catch (\Exception $e) {
            Logger::err('DELETE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

}

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
 * Shopify Product API Manager
 *
 * @author Marco Guiducci
 */
class ShopifyProductAPIManager extends BaseShopifyAPIManager implements APIManagerInterface{

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
    
    public static function createEntity($entity, TargetServer $server) {
        $apiClient = self::getApiInstance($server);

        try {
            $result = $apiClient->Product->post($entity);
            Logger::log('response API: ');
            Logger::log(print_r($result, true));
            Logger::log(($result));
            return $result;
        } catch (Exception $e) {
            Logger::err('CREATE SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }

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
    
    public static function deleteEntity($entityKey, TargetServer $server) {
        throw new \Exception("ERROR - Method 'deleteEntity' not implemented in 'ShopifyProductAPIManager'");
    }

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

    public function updateInventoryInfo ($payload, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            Logger::warn('URL GENERATED');
            $result = $apiClient->InventoryLevel->post($payload, $apiClient->InventoryLevel->generateUrl([], 'set'), false);
            return $result;
        } catch (\Exception $e) {
            Logger::err('UPDATE SHOPIFY INVENTORY LEVELS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function getProductMetafields ($productId, TargetServer $server) : array {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield->get();
            Logger::log('PRODUCT METAFIELDS');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('GET SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return [];
        }
    }

    public function getProductVariantMetafields ($productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield->get();
            Logger::log('PRODUCT VARIANT METAFIELDS');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('GET SHOPIFY VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function createProductMetafield ($payload, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield->post($payload);
            Logger::log('PRODUCT METAFIELDS CREATE');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function updateProductMetafield ($payload, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            unset($payload['namespace']);
            unset($payload['key']);
            $result = $apiClient->Product($productId)->Metafield($payload['id'])->put($payload);
            Logger::log('PRODUCT METAFIELDS UPDATE');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('UPDATE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function deleteProductMetafield ($metafieldId, $productId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Metafield($metafieldId)->delete();
            Logger::log('PRODUCT METAFIELDS DELETED');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('DELETE SHOPIFY Product METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function createProductVariantMetafield ($payload, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield->post($payload);
            Logger::log('PRODUCT VARIANT METAFIELDS CREATE');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function updateProductVariantMetafield ($payload, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            unset($payload['namespace']);
            unset($payload['key']);
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield($payload['id'])->put($payload);
            Logger::log('PRODUCT VARIANT METAFIELDS UPDATED');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('CREATE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

    public function deleteProductVariantMetafield ($metafieldId, $productId, $varId, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);
        try {
            $result = $apiClient->Product($productId)->Variant($varId)->Metafield($metafieldId)->delete();
            Logger::log('PRODUCT VARIANT METAFIELDS DELETED');
            Logger::log(json_encode($result));
            return $result;
        } catch (\Exception $e) {
            Logger::err('DELETE SHOPIFY Product VARIANT METAFIELDS FAILED:' . $e->getMessage());
            return false;
        }
    }

}

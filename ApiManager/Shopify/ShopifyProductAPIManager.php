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

    public function getEntityByKey($entityKey, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);

        try {
            $result = $apiClient->Product($entityKey)->get();
            return $result;
        } catch (Exception $e) {
            Logger::err('SEARCH SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }
    
    public function searchShopifyProducts ($filters, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);

        try {
            $result = $apiClient->Product->get($filters);
            return $result;
        } catch (Exception $e) {
            Logger::err('SEARCH SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }
    
    public function createEntity($entity, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);

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

    public function updateEntity($entityKey, $entity, TargetServer $server) {
        $apiClient = $this->getApiInstance($server);

        try {
            $result = $apiClient->Product($entityKey)->put($entity);
            return $result;
        } catch (Exception $e) {
            Logger::err('UPDATE SHOPIFY PRODUCT ERROR:', $e->getMessage());
            return false;
        }
    }
    
    public function deleteEntity($entityKey, TargetServer $server) {
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

}

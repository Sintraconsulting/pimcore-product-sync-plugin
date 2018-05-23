<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager;

use PHPShopify\ShopifySDK;
use SintraPimcoreBundle\Resources\Ecommerce\ShopifyConfig;
use SpringImport\Swagger\Magento2\Client\Configuration;
use SpringImport\Swagger\Magento2\Client\ApiClient;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;

/**
 * Magento Rest API Manager 
 *
 * @author Marco Guiducci
 */
abstract class AbstractAPIManager {

    /**
     * Get API Client to Perform Rest API calls
     *
     * @return ApiClient The API Client
     */
    public function getMagento2ApiInstance() {
        $magentoConfig = MagentoConfig::getConfig();

        $baseUrl = $magentoConfig['path'] . '/rest';
        $token = 'bearer ' . $magentoConfig['apiKey'];

        $config = new Configuration();
        $config->setHost($baseUrl);
        $config->addDefaultHeader('Authorization', $token);

        return new ApiClient($config);
    }

    public function getShopifyApiInstance () {
        $shopifyConfig = ShopifyConfig::getConfig();
        $config = [
                'ShopUrl' => $shopifyConfig['path'],
                'AccessToken' => $shopifyConfig['apiKey']
        ];
        return new ShopifySDK($config);
    }
    
    public abstract function createEntity($entity);

    public abstract function getEntityByKey($entityKey);
    
    public abstract function deleteEntity($entityKey);
    
    public abstract function updateEntity($entityKey, $entity);

}

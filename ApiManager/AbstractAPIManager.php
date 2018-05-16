<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager;

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
    public function getApiInstance() {
        $magentoConfig = MagentoConfig::getConfig();

        $baseUrl = $magentoConfig['path'] . '/rest';
        $token = 'bearer ' . $magentoConfig['apiKey'];

        $config = new Configuration();
        $config->setHost($baseUrl);
        $config->addDefaultHeader('Authorization', $token);

        return new ApiClient($config);
    }
    
    public abstract function createEntity($entity);

    public abstract function getEntityByKey($entityKey);
    
    public abstract function deleteEntity($entityKey);
    
    public abstract function updateEntity($entityKey, $entity);

}

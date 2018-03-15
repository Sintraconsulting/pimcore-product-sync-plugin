<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Magento2PimcoreBundle\ApiManager;

use SpringImport\Swagger\Magento2\Client\Configuration;
use SpringImport\Swagger\Magento2\Client\ApiClient;
use Magento2PimcoreBundle\Resources\Magento\MagentoConfig;

//include_once 'vendor/autoload.php';
//include_once 'vendor/springimport/swagger-magento2-client/lib/Configuration.php';
//include_once 'vendor/springimport/swagger-magento2-client/lib/ApiClient.php';
//require_once 'src/Magento2PimcoreBundle/Resources/magento/MagentoConfig.php';

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

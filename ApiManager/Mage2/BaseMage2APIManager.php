<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager\Mage2;

use SintraPimcoreBundle\ApiManager\AbstractAPIManager;
use Pimcore\Model\DataObject\TargetServer;

use SpringImport\Swagger\Magento2\Client\Configuration;
use SpringImport\Swagger\Magento2\Client\ApiClient;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;

/**
 * Base Magento2 API Manager
 *
 * @author Marco Guiducci
 */
class BaseMage2APIManager extends AbstractAPIManager{

    public function getApiInstance(TargetServer $server) {
        $magentoConfig = MagentoConfig::getConfig();

        $baseUrl = $magentoConfig['path'] . '/rest';
        $token = 'bearer ' . $magentoConfig['apiKey'];

        $config = new Configuration();
        $config->setHost($baseUrl);
        $config->addDefaultHeader('Authorization', $token);

        return new ApiClient($config);
    }

}

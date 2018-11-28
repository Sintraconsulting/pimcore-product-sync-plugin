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

use Pimcore\Model\DataObject\Objectbrick\Data\Mage2ServerInfo;

/**
 * Magento2 API Manager Base Class
 * Will be extended by each class that performs API calls to a Magento2 server
 *
 * @author Sintra Consulting
 */
class BaseMage2APIManager extends AbstractAPIManager{

    /**
     * Get the API Client Instance for a Magento2 Server.
     * Retrieve Server URL and API Key for authentication from the TargetServe object
     * 
     * @param TargetServer $server 
     * @return ApiClient
     */
    protected static function getApiInstance(TargetServer $server) {
        $serverInfo = self::getServerInfo($server);

        $baseUrl = $server->getServerBaseUrl() . '/rest/all';
        $token = 'bearer ' . $serverInfo->getApiKey();

        $config = new Configuration();
        $config->setHost($baseUrl);
        $config->addDefaultHeader('Authorization', $token);

        return new ApiClient($config);
    }
    
    /**
     * Get Server Info from the specific ObjectBrick
     * Throw an exception if the ObjectBrick is not valid fo Magento2
     * 
     * @param TargetServer $server the server
     * @return Mage2ServerInfo the server info
     * @throws \Exception
     */
    private static function getServerInfo(TargetServer $server){
        $serverInfos = $server->getServerInfo()->getItems();
        
        $serverInfo = $serverInfos[0];
        if($serverInfo->getType() != "Mage2ServerInfo"){
            throw new \Exception("BaseMage2APIManager ERROR - ServerInfo must be an instance of 'Mage2ServerInfo' for Magento. "
                    . "'".$serverInfo->getType()."' given");
        }
        
        return $serverInfo;
    }

}

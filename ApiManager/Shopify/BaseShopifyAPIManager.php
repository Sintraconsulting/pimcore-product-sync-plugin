<?php

namespace SintraPimcoreBundle\ApiManager\Shopify;

use PHPShopify\ShopifySDK;
use Pimcore\Model\DataObject\Objectbrick\Data\ShopifyServerInfo;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\AbstractAPIManager;

/**
 * Shopify API Manager Base Class
 * Will be extended by each class that performs API calls to a Shopify server
 *
 * @author Sintra Consulting
 */
class BaseShopifyAPIManager extends AbstractAPIManager{
    
    /**
     * Get the API Client Instance for a Shopify Server.
     * Retrieve Server URL, API Key and API Password for authentication 
     * from the TargetServe object
     * 
     * @param TargetServer $server 
     * @return ApiClient
     */
    protected static function getApiInstance(TargetServer $server) {
        $serverInfo = self::getServerInfo($server);
        
        $config = [
            'ShopUrl' => $server->getServerBaseUrl(),
            'ApiKey' => $serverInfo->getApiKey(),
            'Password' => $serverInfo->getApiPassword()
        ];
        return new ShopifySDK($config);
    }
    
    /**
     * Get Server Info from the specific ObjectBrick
     * Throw an exception if the ObjectBrick is not valid fo Shopify
     * 
     * @param TargetServer $server the server
     * @return ShopifyServerInfo the server info
     * @throws \Exception
     */
    private static function getServerInfo(TargetServer $server){
        $serverInfos = $server->getServerInfo()->getItems();
        
        $serverInfo = $serverInfos[0];
        if($serverInfo->getType() != "ShopifyServerInfo"){
            throw new \Exception("BaseShopifyAPIManager ERROR - ServerInfo must be an instance of 'ShopifyServerInfo' for Shopify. "
                    . "'".$serverInfo->getType()."' given");
        }
        
        return $serverInfo;
    }


}

<?php

namespace SintraPimcoreBundle\ApiManager\Shopify;

use PHPShopify\ShopifySDK;
use Pimcore\Model\DataObject\Objectbrick\Data\ShopifyServerInfo;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\AbstractAPIManager;

use Pimcore\Logger;

/**
 * Shopify API Manager
 *
 * @author Marco Guiducci
 */
class BaseShopifyAPIManager extends AbstractAPIManager{
    
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
     * get server info from object brick
     * 
     * @param TargetServer $server the server
     * @return ShopifyServerInfo the server info
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

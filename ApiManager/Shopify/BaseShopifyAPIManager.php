<?php

namespace SintraPimcoreBundle\ApiManager\Shopify;

use SintraPimcoreBundle\ApiManager\AbstractAPIManager;
use Pimcore\Model\DataObject\TargetServer;

use PHPShopify\ShopifySDK;
use SintraPimcoreBundle\Resources\Ecommerce\ShopifyConfig;

/**
 * Shopify API Manager
 *
 * @author Marco Guiducci
 */
class BaseShopifyAPIManager extends AbstractAPIManager{
    
    public function getApiInstance(TargetServer $server) {
        $shopifyConfig = ShopifyConfig::getConfig();
        $config = [
                'ShopUrl' => $shopifyConfig['path'],
                'AccessToken' => $shopifyConfig['apiKey']
        ];
        return new ShopifySDK($config);
    }
    
    

}

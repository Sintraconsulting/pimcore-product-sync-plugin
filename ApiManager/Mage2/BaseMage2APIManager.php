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
 * Base Magento2 API Manager
 *
 * @author Marco Guiducci
 */
class BaseMage2APIManager extends AbstractAPIManager{

    public function getApiInstance(TargetServer $server) {
        $serverInfo = $this->getServerInfo($server);

        $baseUrl = $server->getServerBaseUrl() . '/rest';
        $token = 'bearer ' . $serverInfo->getApiKey();

        $config = new Configuration();
        $config->setHost($baseUrl);
        $config->addDefaultHeader('Authorization', $token);

        return new ApiClient($config);
    }
    
    /**
     * get server info from object brick
     * 
     * @param TargetServer $server the server
     * @return Mage2ServerInfo the server info
     */
    private function getServerInfo(TargetServer $server){
        $serverInfos = $server->getServerInfo()->getItems();
        
        $serverInfo = $serverInfos[0];
        if($serverInfo->getType() != "Mage2ServerInfo"){
            throw new \Exception("BaseMage2APIManager ERROR - ServerInfo must be an instance of 'Mage2ServerInfo' for Magento. "
                    . "'".$serverInfo->getType()."' given");
        }
        
        return $serverInfo;
    }

}

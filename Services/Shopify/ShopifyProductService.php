<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Product;
use Pimcore\Logger;
use SintraPimcoreBundle\ApiManager\Shopify\ShopifyProductAPIManager;
use SintraPimcoreBundle\Services\InterfaceService;

class ShopifyProductService extends BaseShopifyService implements InterfaceService {
    protected $configFile = __DIR__ . '/../config/product.json';

    /**
     * 
     * @param Product $dataObject
     * @param TargetServer $targetServer
     */
    public function export ($dataObject, TargetServer $targetServer) {
        $shopifyProduct = json_decode(file_get_contents($this->configFile), true)[$targetServer->getKey()];
        
        $apiManager = ShopifyProductAPIManager::getInstance();
        $serverObjectInfo = $this->getServerObjectInfo($dataObject, $targetServer);
        
        $shopifyId = $serverObjectInfo->getObject_id();
        
        $search = array();
        if($shopifyId != null && !empty($shopifyId)){
            $search = $apiManager->getEntityByKey($shopifyId, $targetServer);
            Logger::info("SEARCH RESULT: $shopifyId".print_r($search,true));
        }

        if (count($search) === 0) {
            //product is new, need to save price
            $this->toEcomm($shopifyProduct, $dataObject, $targetServer, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            
            $result = $apiManager->createEntity($shopifyProduct, $targetServer);
            $serverObjectInfo->setSync_at($result["updated_at"]);
            $serverObjectInfo->setObject_id($result['id']);
        } else if (count($search) === 1){
            $shopifyProduct["id"] = $search[0]['id'];
            
            //product already exists, we may want to not update prices
            $this->toEcomm($shopifyProduct, $dataObject, $targetServer, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            
            $result = $apiManager->updateEntity($search[0]['id'], $shopifyProduct, $targetServer);
            $serverObjectInfo->setSync_at($result[0]["updated_at"]);
        }
        Logger::debug("SHOPIFY UPDATED PRODUCT: " . json_encode($result));

        $serverObjectInfo->setSync(true);
        
        try {
            $dataObject->update(true);
        } catch (\Exception $e) {
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     * 
     * @param Product $dataObject
     * @param TargetServer $targetServer
     * @param bool $update
     */
    public function toEcomm (&$ecommObject, $dataObject, TargetServer $targetServer, bool $update = false) {
        
        if (!$update) {
            unset($ecommObject["variant"][0]["price"]);
        }

        $exportMap = $targetServer->getExportMap()->getItems();
        $languages = $targetServer->getLanguages();
        
        foreach ($exportMap as $fieldMap) {
            //get the value of each object field
            $objectField = $this->getObjectField($fieldMap, $languages[0], $dataObject);
            
            //get the name of the related field in server from field mapping
            $serverField = $fieldMap->getServerField();
            $this->mapField($ecommObject, $serverField, $objectField);
        }

        //return $ecommObject;
    }
}
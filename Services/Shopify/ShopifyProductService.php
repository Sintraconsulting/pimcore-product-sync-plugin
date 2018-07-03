<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Product;
use Pimcore\Logger;
use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\ShopifyConfig;
use SintraPimcoreBundle\Services\InterfaceService;

class ShopifyProductService extends BaseShopifyService implements InterfaceService {
    protected $configFile = __DIR__ . '/../config/product.json';

    /**
     * 
     * @param Product $dataObject
     * @param TargetServer $targetServer
     */
    public function export ($dataObject, TargetServer $targetServer) {
        $apiManager = ProductAPIManager::getInstance();
        $serverObjectInfo = $this->getServerObjectInfo($dataObject, $targetServer);
        
        $shopifyId = $serverObjectInfo->getObject_id();
        $search = $apiManager->searchShopifyProducts([
                'ids' => (int) $shopifyId
        ]);

        if (count($search) === 0) {
            //product is new, need to save price
            $shopifyProduct = $this->toEcomm($dataObject, $targetServer, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            
            $result = $apiManager->createShopifyEntity($shopifyProduct);
            $serverObjectInfo->setSync_at($result["updated_at"]);
            $serverObjectInfo->setObject_id($result['id']);
        } else if (count($search) === 1){
            //product already exists, we may want to not update prices
            $shopifyProduct = $this->toEcomm($dataObject, $targetServer, ShopifyConfig::$updateProductPrices);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            
            $result = $apiManager->updateShopifyEntity($shopifyProduct, $search[0]['id']);
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
     * 
     * @param Product $dataObject
     * @param TargetServer $targetServer
     * @param bool $update
     */
    public function toEcomm ($dataObject, TargetServer $targetServer, bool $update = false) {
        $product = json_decode(file_get_contents($this->configFile), true)[$targetServer->getKey()];
        if (!$update) {
            unset($product["variant"][0]["price"]);
        }

        $exportMap = $targetServer->getExportMap()->getItems();
        $languages = $targetServer->getLanguages();
        foreach ($exportMap as $fieldMap) {
            $objectField = $this->getObjectField($fieldMap, $languages[0], $dataObject);
            
            $serverField= $fieldMap->getServerField();
            $this->mapField($product, $serverField, $objectField);
        }

        return $product;
    }
}
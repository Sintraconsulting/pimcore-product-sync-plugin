<?php

namespace SintraPimcoreBundle\Services\Shopify;


use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\ShopifyConfig;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Logger;

class ShopifyProductService extends BaseShopifyService implements InterfaceService {
    protected $configFile = __DIR__ . '/../config/product.json';

    public function export ($dataObject) {
        $apiManager = ProductAPIManager::getInstance();
        $shopifyId = $dataObject->getShopify_id();
        $search = $apiManager->searchShopifyProducts([
                'ids' => (int) $shopifyId
        ]);

        if (count($search) === 0) {
            //product is new, need to save price
            $shopifyProduct = $this->toEcomm($dataObject, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            $result = $apiManager->createShopifyEntity($shopifyProduct);
            $dataObject->setShopify_sync_at($result["updated_at"]);
            $dataObject->setShopify_id($result['id']);
        } else if (count($search) === 1){
            //product already exists, we may want to not update prices
            $shopifyProduct = $this->toEcomm($dataObject, ShopifyConfig::$updateProductPrices);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyProduct));
            $result = $apiManager->updateShopifyEntity($shopifyProduct, $search[0]['id']);
            $dataObject->setShopify_sync_at($result[0]["updated_at"]);
        }
        Logger::debug("SHOPIFY UPDATED PRODUCT: " . json_encode($result));

        $dataObject->setShopify_sync(true);

        try {
            $dataObject->update(true);
        } catch (\Exception $e) {
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    public function toEcomm ($dataObject, bool $update = false) {
        $product = json_decode(file_get_contents($this->configFile), true)['shopify'];
        if (!$update) {
            unset($product["variant"][0]["price"]);
        }

        $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            $fieldValue = $dataObject->getValueForFieldName($fieldName);

            $this->mapField($product, $fieldName, $fieldType, $fieldValue, $dataObject->getClassId());
        }

        return $product;
    }
}
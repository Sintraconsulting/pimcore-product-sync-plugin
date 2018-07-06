<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Product;
use Pimcore\Logger;
use SintraPimcoreBundle\ApiManager\Shopify\ShopifyProductAPIManager;
use SintraPimcoreBundle\Services\InterfaceService;

class ShopifyProductService extends BaseShopifyService implements InterfaceService {
    protected $configFile = __DIR__ . '/../config/product.json';

    /**
     * 
     * @param Product\Listing $dataObject
     * @param TargetServer $targetServer
     */
    public function export ($dataObjects, TargetServer $targetServer) {
        /** @var Product $dataObject */
        $dataObject = $dataObjects->current();
//        $shopifyProduct = json_decode(file_get_contents($this->configFile), true)[$targetServer->getKey()];
        $shopifyApi = [];
        /** @var ShopifyProductAPIManager $apiManager */
        $apiManager = ShopifyProductAPIManager::getInstance();
        $serverObjectInfo = $this->getServerObjectInfo($dataObject, $targetServer);
        
        $shopifyId = $serverObjectInfo->getObject_id();

        $search = array();
        if($shopifyId != null && !empty($shopifyId)){
            $search = $apiManager->searchShopifyProducts(['ids' => $shopifyId],$targetServer);
            Logger::info("SEARCH RESULT: $shopifyId".print_r($search,true));
        }

        if (count($search) === 0) {
            //product is new, need to save price
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyApi));

            /** @var ShopifyProductAPIManager $apiManager */
            $result = $apiManager->createEntity($shopifyApi, $targetServer);
        } else if (count($search) === 1){
            $shopifyApi["id"] = $search[0]['id'];
            
            //product already exists, we may want to not update prices
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyApi));
            /** @var ShopifyProductAPIManager $apiManager */
            $result = $apiManager->updateEntity($shopifyId, $shopifyApi, $targetServer);
        }
        Logger::debug("SHOPIFY UPDATED PRODUCT: " . json_encode($result));

        try {
            $this->setSyncProducts($result, $targetServer);
        } catch (\Exception $e) {
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $shopifyApi
     * @param Product\Listing $dataObjects
     * @param TargetServer $targetServer
     * @param bool $update
     */
    public function toEcomm (&$shopifyApi, $dataObjects, TargetServer $targetServer, bool $update = false) {

        $exportMap = $targetServer->getExportMap()->getItems();
        $languages = $targetServer->getLanguages();

        $shopifyApi = $this->prepareVariants($shopifyApi, $dataObjects, $targetServer);

        /** @var FieldMapping $fieldMap */
        foreach ($exportMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            $fieldsDepth = explode('.', $apiField);
            $shopifyApi = $this->mapServerMultipleField($shopifyApi, $fieldMap, $fieldsDepth, $languages[0], $dataObjects, $targetServer);
//            if ($depth > 1) {
//                $shopifyApi = $this->mapServerMultipleField($shopifyApi, $fieldMap, $fieldsDepth, $languages[0], $dataObjects);
//            } else {
//                $objectFieldValue = $this->getObjectField($fieldMap, $languages[0], $dataObjects->current());
//                $shopifyApi = $this->mapServerField($shopifyApi, $objectFieldValue, $apiField);
//            }
            //get the name of the related field in server from field mapping
        }

        //return $ecommObject;
    }

    /**
     * @param $shopifyApi
     * @param Product\Listing $products
     */
    public function prepareVariants($shopifyApi, $products, TargetServer $server) {
        $shopifyApi['variants'] = [];
        foreach ($products as $product) {
            $serverObjectInfo = $this->getServerObjectInfo($product, $server);
            $varId = $serverObjectInfo->getVariant_id();
            if ($varId) {
                $shopifyApi['variants'][] = [
                        'id' => $varId
                ];
            } else {
                $shopifyApi['variants'][] = [];
            }
        }
        return $shopifyApi;
    }

    protected function setSyncProducts ($results, $targetServer) {
        if (is_array($results)) {
            foreach ($results['variants'] as $variant) {
                $product = Product::getBySku($variant['sku'])->current();
                $serverObjectInfo = $this->getServerObjectInfo($product, $targetServer);
                $serverObjectInfo->setSync(true);
                $serverObjectInfo->setSync_at($results["updated_at"]);
                $serverObjectInfo->setObject_id($results['id']);
                $serverObjectInfo->setVariant_id($variant['id']);
                $product->update(true);
            }
        }
    }
}
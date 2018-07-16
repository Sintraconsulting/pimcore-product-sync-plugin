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
     * @param $productId
     * @param TargetServer $targetServer
     */
    public function export ($productId, TargetServer $targetServer) {
        $dataObjects = $this->getObjectsToExport($productId, "Product");
        
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
            Logger::info("SEARCH RESULT: $shopifyId".json_encode($search));
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
            Logger::debug("SHOPIFY PRODUCT EDIT: " . json_encode($shopifyApi));
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

        }

        return $shopifyApi;

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

    /**
     * Specific mapping for Shopify Product export
     * It builds the API array for communcation with shopify product endpoint
     * @param $shopifyApi
     * @param $fieldMap
     * @param $fieldsDepth
     * @param $language
     * @param null $dataSource
     * @param null $server
     * @return array
     * @throws \Exception
     */
    protected function mapServerMultipleField ($shopifyApi, $fieldMap, $fieldsDepth, $language, $dataSource = null, $server = null) {
        // End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Product\Listing $dataSource */
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            if($fieldValue instanceof \Pimcore\Model\DataObject\Data\QuantityValue && $apiField == 'weight'){
                return $this->mapServerField($shopifyApi, $fieldValue->getValue(), $apiField) + $this->mapServerField([], $fieldValue->getUnit()->getAbbreviation(), 'weight_unit');
            }
            return $this->mapServerField($shopifyApi, $fieldValue, $apiField);
        }
        $parentDepth = array_shift($fieldsDepth);

        //Recursion inside variants
        if ($parentDepth == 'variants' && $dataSource) {
            $i = 0;
            foreach ($dataSource as $dataObject) {
                $serverInfo = $this->getServerObjectInfo($dataObject, $server);
                if (!$serverInfo->getSync()) {
                    $shopifyApi[$parentDepth][$i] = $this->mapServerMultipleField($shopifyApi[$parentDepth][$i],
                            $fieldMap, $fieldsDepth, $language, $dataObject);
                }
                $i++;
            }
            return $shopifyApi;
        }

        /**
         * End of recursion with metafields
         * @see https://help.shopify.com/en/api/reference/metafield
         * TODO: could be exported as a self sustainable function, but for now it's not necessary
         */
        if ($parentDepth == 'metafields') {
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            $fieldType = is_integer($fieldValue) ? 'integer' : 'string';
            $customValue = [
                    'key' => $apiField,
                    'value' => $fieldType === 'string' ? (string)$fieldValue : $fieldValue,
                    'value_type' => $fieldType,
                    // Namespace is intentional like this so we know it was generated by SintraPimcoreBundle
                    'namespace' => 'SintraPimcore',
            ];
            $shopifyApi[$parentDepth][] = $customValue;
            return $shopifyApi;
        }

        /**
         * Recursion level > 1
         * For now, on shopify there is no nested field mapping except metafields & variants
         * It should never reach this point with shopify.
         * TODO: image implementation should be developed in the future here for field mapping
         */
        return $this->mapServerMultipleField($shopifyApi[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
    }
}
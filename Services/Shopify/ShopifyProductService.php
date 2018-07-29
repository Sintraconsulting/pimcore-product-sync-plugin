<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Product;
use Pimcore\Logger;
use SintraPimcoreBundle\ApiManager\Shopify\ShopifyProductAPIManager;
use SintraPimcoreBundle\Services\InterfaceService;
use SintraPimcoreBundle\Utils\GeneralUtils;
use SintraPimcoreBundle\Utils\TargetServerUtils;

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
        
        $shopifyApi = [];
        
        /** @var ShopifyProductAPIManager $apiManager */
        $apiManager = ShopifyProductAPIManager::getInstance();
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        $shopifyId = $serverObjectInfo->getObject_id();

        $search = array();
        if($shopifyId != null && !empty($shopifyId)){
            $search = $apiManager->searchShopifyProducts(['ids' => $shopifyId],$targetServer);
            Logger::info("SEARCH RESULT: $shopifyId".json_encode($search));
        }

        if (count($search) === 0) {
            //product is new, need to save price
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, $dataObject->getClassName(), true);

            $shopifyObj = new ShopifyProductModel($dataObjects, $shopifyApi, null, $targetServer);
            $shopifyApi = $shopifyObj->getParsedShopifyApiRequest(true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyApi));

            /** @var ShopifyProductAPIManager $apiManager */
            $result = $apiManager->createEntity($shopifyApi, $targetServer);
        } else if (count($search) === 1){
            $shopifyApi["id"] = $search[0]['id'];
            //product already exists, we may want to not update prices
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, $dataObject->getClassName(), true);

            $shopifyObj = new ShopifyProductModel($dataObjects, $shopifyApi, $search[0], $targetServer);
            $shopifyApi = $shopifyObj->getParsedShopifyApiRequest(false);

            Logger::debug("SHOPIFY PRODUCT EDIT: " . json_encode($shopifyApi));
//            $shopifyObj->updateAndCacheMetafields();
            return;
            /** @var ShopifyProductAPIManager $apiManager */
            $result = $apiManager->updateEntity($shopifyId, $shopifyApi, $targetServer);
        }
        Logger::debug("SHOPIFY UPDATED PRODUCT: " . json_encode($result));

        try {
            $this->setSyncProducts($result, $targetServer);
            $shopifyObj->updateShopifyResponse($result);
            $shopifyObj->updateAndCacheMetafields(count($search) === 0);
            $shopifyObj->updateInventoryApiResponse();
            $shopifyObj->updateVariantsInventories();
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
    public function toEcomm (&$shopifyApi, $dataObjects, TargetServer $targetServer, $classname, bool $update = false) {

        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $classname);
        $languages = $targetServer->getLanguages();

        $shopifyApi = $this->prepareVariants($shopifyApi, $dataObjects, $targetServer);

        /** @var FieldMapping $fieldMap */
        foreach ($fieldsMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            $fieldsDepth = explode('.', $apiField);
            $shopifyApi = $this->mapServerMultipleField($shopifyApi, $fieldMap, $fieldsDepth, $languages[0], $dataObjects, $targetServer);

        }

        return $shopifyApi;

    }

    /**
     * @param $shopifyApi
     * @param Product\Listing $products
     */
    public function prepareVariants($shopifyApi, $products, TargetServer $server) {
        $shopifyApi['variants'] = [];
        foreach ($products as $product) {
            /** @var ServerObjectInfo $serverObjectInfo */
            $serverObjectInfo = GeneralUtils::getServerObjectInfo($product, $server);
            $varId = $serverObjectInfo->getVariant_id();
            if ($varId) {
                $shopifyApi['variants'][] = [
                        'id' => $varId,
                        'inventory_management' => 'shopify'
                ];
            } else {
                $shopifyApi['variants'][] = [
                        'inventory_management' => 'shopify'
                ];
            }
        }
        return $shopifyApi;
    }

    protected function setSyncProducts ($results, $targetServer) {
        if (is_array($results)) {
            foreach ($results['variants'] as $variant) {
                $product = Product::getBySku($variant['sku'])->current();
                /** @var ServerObjectInfo $serverObjectInfo */
                $serverObjectInfo = GeneralUtils::getServerObjectInfo($product, $targetServer);
                $serverObjectInfo->setSync(true);
                ## Mimic the shopify date format
                $serverObjectInfo->setSync_at(date('Y-m-d') . 'T'. date('H:i:sP'));
                $serverObjectInfo->setObject_id($results['id']);
                $serverObjectInfo->setVariant_id($variant['id']);
                $serverObjectInfo->setInventory_id($variant['inventory_item_id']);
                $product->update(true);
            }
        }
    }

}
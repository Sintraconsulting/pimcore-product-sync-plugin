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

/**
 * Implement methods for products synchronization on Shopify servers
 *
 * @author Sintra Consulting
 */
class ShopifyProductService extends BaseShopifyService implements InterfaceService {
    /**
     * Export specific product based on $productId
     * If there is an error while exporting, the product will remain flagged as not synced
     * but the successful updates until that point remain saved
     *
     * @param $productId
     * @param TargetServer $targetServer
     * @throws \Exception
     */
    public function export ($productId, TargetServer $targetServer) {
        /** @var Product\Listing $dataObjects */
        $dataObjects = $this->getObjectsToExport($productId, "Product", $targetServer);

        /** @var Product $dataObject */
        # Get Product object because we receive a Listing from getObjectsToExport
        $dataObject = $dataObjects->current();

        $shopifyApi = [];

        # Retrieve Product's Server information based on $targetServer
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        # Shopify's Product id
        $shopifyId = $serverObjectInfo->getObject_id();

        $search = array();

        # If there is already an shopify Product associated with this pimcore product
        if($shopifyId != null && !empty($shopifyId)){
            # Get information of the shopify Product
            $search = ShopifyProductAPIManager::searchShopifyProducts(['ids' => $shopifyId],$targetServer);
            Logger::info("SEARCH RESULT: $shopifyId".json_encode($search));
        }

        if (count($search) === 0) {
            //product is new, need to save price
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, $dataObject->getClassName(), true);

            $shopifyObj = new ShopifyProductModel($dataObjects, $shopifyApi, null, $targetServer);
            $shopifyApi = $shopifyObj->getParsedShopifyApiRequest(true);
            Logger::debug("SHOPIFY PRODUCT: " . json_encode($shopifyApi));

            /** @var ShopifyProductAPIManager $apiManager */
            $result = ShopifyProductAPIManager::createEntity($shopifyApi, $targetServer);
        } else if (count($search) === 1){
            $shopifyApi["id"] = $search[0]['id'];
            //product already exists, we may want to not update prices
            $this->toEcomm($shopifyApi, $dataObjects, $targetServer, $dataObject->getClassName(), true);

            $shopifyObj = new ShopifyProductModel($dataObjects, $shopifyApi, $search[0], $targetServer);
            $shopifyApi = $shopifyObj->getParsedShopifyApiRequest(false);

            Logger::debug("SHOPIFY PRODUCT EDIT: " . json_encode($shopifyApi));
            /** @var ShopifyProductAPIManager $apiManager */
            $result = ShopifyProductAPIManager::updateEntity($shopifyId, $shopifyApi, $targetServer);
        }
        Logger::debug("SHOPIFY UPDATED PRODUCT: " . json_encode($result));

        $this->setProductServerInfos($result, $targetServer);
        $shopifyObj->updateShopifyResponse($result);
        $shopifyObj->updateAndCacheMetafields(count($search) === 0);
        $shopifyObj->updateImagesAndCache();
        $shopifyObj->updateInventoryApiResponse();
        $shopifyObj->updateVariantsInventories();

        $this->setSyncProduct($result, $targetServer);

    }

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $shopifyApi
     * @param $dataObjects
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $update
     * @return array
     * @throws \Exception
     */
    public function toEcomm (&$shopifyApi, $dataObjects, TargetServer $targetServer, $classname, bool $update = false) {

        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $classname);
        $languages = $targetServer->getLanguages();
        # Pre-build the API to include the variants
        $shopifyApi = $this->prepareVariants($shopifyApi, $dataObjects, $targetServer);

        /** @var FieldMapping $fieldMap */
        foreach ($fieldsMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            # Create an array with depth based on char . for nesting
            $fieldsDepth = explode('.', $apiField);
            $shopifyApi = $this->mapServerMultipleField($shopifyApi, $fieldMap, $fieldsDepth, $languages[0], $dataObjects, $targetServer);

        }

        return $shopifyApi;

    }

    /**
     * Prepares variants with ID if they already exist.
     * If any product is not published in pimcore, it sets the entire shopify Product as unpublished
     *
     * @param $shopifyApi
     * @param Product\Listing $products
     * @param TargetServer $server
     * @return mixed
     */
    public function prepareVariants($shopifyApi, $products, TargetServer $server) {
        $shopifyApi['variants'] = [];
        $published = true;
        /** @var Product $product */
        foreach ($products as $product) {
            # If there is at least one variant that is not published, entire Product is set as unpublished
            if ($published && !$product->getPublished()) {
                $published = false;
            }
            /** @var ServerObjectInfo $serverObjectInfo */
            $serverObjectInfo = GeneralUtils::getServerObjectInfo($product, $server);
            $varId = $serverObjectInfo->getVariant_id();
            if ($varId) {
                $shopifyApi['variants'][] = [
                        # Set id to avoid recreation
                        'id' => $varId,
                        'inventory_management' => 'shopify'
                ];
            } else {
                $shopifyApi['variants'][] = [
                        'inventory_management' => 'shopify'
                ];
            }
        }
        if (!$published) {
            $shopifyApi['published'] = false;
        } else {
            $shopifyApi['published'] = true;
        }
        return $shopifyApi;
    }

    /**
     * Sets the shopify Product id and ProductVariant id inside ServerObjectInfo of every pimcore product
     *
     * @param $results
     * @param $targetServer
     */
    protected function setProductServerInfos ($results, $targetServer) {
        if (is_array($results)) {
            foreach ($results['variants'] as $variant) {
                # Load also unpublished products
                $product = Product::getBySku($variant['sku'])->setUnpublished(true)->current();
                if($product){
                    /** @var ServerObjectInfo $serverObjectInfo */
                    $serverObjectInfo = GeneralUtils::getServerObjectInfo($product, $targetServer);

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

    protected function setSyncProduct ($results, $targetServer) {
        if (is_array($results)) {
            foreach ($results['variants'] as $variant) {
                /** @var Product $product */
                $product = Product::getBySku($variant['sku'])->setUnpublished(true)->current();
                if($product){
                    /** @var ServerObjectInfo $serverObjectInfo */
                    $serverObjectInfo = GeneralUtils::getServerObjectInfo($product, $targetServer);
                    if ($this->checkAllVariantImagesSynced($product, $serverObjectInfo)) {
                        $serverObjectInfo->setSync(true);
                    } else {
                        $serverObjectInfo->setSync(false);
                    }
                    $product->update(true);
                }
            }
        }
    }

    protected function checkAllVariantImagesSynced (Product $variant, ServerObjectInfo $serverObjectInfo) {
        $images = $variant->getImages();
        return ($images == null || count($images->getItems()) === 0 || (int)$serverObjectInfo->getImages_sync() === 1);
    }

    protected function millitime() {
        $microtime = microtime();
        $comps = explode(' ', $microtime);

        // Note: Using a string here to prevent loss of precision
        // in case of "overflow" (PHP converts it to a double)
        return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
    }

}

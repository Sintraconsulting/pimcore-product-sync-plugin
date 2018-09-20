<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Grpc\Server;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Tool\RestClient\Exception;
use SintraPimcoreBundle\ApiManager\Shopify\ShopifyProductAPIManager;

class ShopifyProductModel {

    /** @var Product\Listing */
    protected $rawVariants;
    /**
     * Product Objects from PimCore database
     * @var array $variants
     */
    protected $variants = [];
    /**
     * Metafields parsed from cache
     * @var array $metafields
     */
    protected $metafields = [];
    /**
     * Metafields to be updated
     * @var array $updateMetafields
     */
    protected $updateMetafields = [
            'product' => [],
            'variants' => []
    ];
    /**
     * Shopify Object from search / create
     * @var array $shopifyModel
     */
    protected $shopifyModel;
    /**
     * Json Prebuilt for API request
     * @var array $shopifyApiReq
     */
    protected $shopifyApiReq;
    /**
     * Target Server for export
     * @var TargetServer $targetServer
     */
    protected $targetServer;
    /**
     * Shopify API Manager
     * @var ShopifyProductAPIManager
     */
    protected $apiManager;
    /**
     * Product Server Information
     * @var array
     */
    protected $serverInfos = [];
    /**
     * Variant unifier
     * @var string
     */
    protected $hook;

    public function __construct (Product\Listing $variants, $shopifyApiReq, $shopifyModel, $targetServer) {
        $this->rawVariants = $variants;
        $this->shopifyApiReq = $shopifyApiReq;
        $this->shopifyModel = $shopifyModel;
        $this->targetServer = $targetServer;
        $this->apiManager = (new ShopifyProductAPIManager());
        $this->buildCustomModelInfo($variants);
        $this->metafields = $this->getAllMetafields();
    }

    protected function getProductsImagesArray () {
        $imgsArray = [];
        /**
         * @var int $id
         * @var Product $variant
         */
        foreach ($this->variants as $id => $variant) {
            $i = count($imgsArray);
            $prodImgsArray = new ShopifyProductImageModel($variant, $this->serverInfos[$id], $i);
            $imgsArray = array_merge($imgsArray, $prodImgsArray->getImagesArray());
        }
        return $imgsArray;
    }

    public function setSyncHook ($hook) {
        $this->hook = $hook;
        $this->buildCustomModelInfo($this->rawVariants);
    }

    public function updateImagesAndCache () {
        /** @var ServerObjectInfo $serverInfo */
        $serverInfo = $this->serverInfos[reset($this->variants)->getId()];
        $updateImagesApiReq = [
                'id' => $serverInfo->getObject_id(),
                'images' => $this->getProductsImagesArray()
        ];
        Logger::log('BEFORE UPDATE IMAGES!');
        Logger::log(json_encode($updateImagesApiReq));
        $result = $this->apiManager::updateEntity($serverInfo->getObject_id(), $updateImagesApiReq, $this->targetServer);
        Logger::log('UPDATE IMAGES RESPONSE!');
        Logger::log(json_encode($result));
        if (isset($result['images']) && count($result['images'])) {
            /** @var Product $currentVar */
            $currentVar = null;
            $currentVarImgs = [];
            foreach ($result['images'] as $i => $image) {
                if (isset($image['variant_ids']) && count($image['variant_ids']) > 0) {
                    if (isset($currentVar) && count($currentVarImgs) > 0) {
                        $this->updateImagesCache($currentVar->getId(), $currentVarImgs);
                    }
                    $currentVar = $this->getVariantByShopifyVariantId($image['variant_ids'][0]);
                    $currentVarImgs = [];
                }
                $currentVarImgs[] = [
                        'id' => $image['id'],
                        'position' => $image['position'],
                        'product_id' => $image['product_id'],
                        'hash' => $updateImagesApiReq['images'][$i]['hash'],
                        'name' => $updateImagesApiReq['images'][$i]['name'],
                        'pimcore_index' => $updateImagesApiReq['images'][$i]['pimcore_index'],
                        'variant_ids' => $image['variant_ids']
                ];
                if (count($result['images']) == $i+1) {
                    if (isset($currentVar)) {
                        $this->updateImagesCache($currentVar->getId(), $currentVarImgs);
                    }
                }
            }
        }
    }

    public function updateShopifyResponse (array $shopifyModel) {
        if (is_array($shopifyModel)) {
            $this->shopifyModel = $shopifyModel;
        }
        $this->buildCustomModelInfo($this->rawVariants);
    }

    public function getParsedShopifyApiRequest ($isCreate = true) {
        $cpyShopifyApiReq = $this->shopifyApiReq;
        $cpyShopifyApiReq =  $isCreate ?
                $this->removeMetafieldsFromApiReq($cpyShopifyApiReq) :
                $this->stripMetafields($cpyShopifyApiReq);
        $cpyShopifyApiReq = $this->mergeVariantsTags($cpyShopifyApiReq);
        return $cpyShopifyApiReq;
    }

    protected function mergeVariantsTags (array $shopifyApiReq) {
        $tags = isset($shopifyApiReq['tags']) ? explode(", ", $shopifyApiReq['tags']): [];
        foreach ($shopifyApiReq['variants'] as $key => $variant) {
            $varTags = explode(", ", $variant['tags']);
            if (empty($varTags[count($varTags) - 1])) {
                array_pop($varTags);
            }
            $tags = array_merge($varTags, $tags);
            unset($shopifyApiReq[$key][$variant]['tags']);
        }
        $shopifyApiReq['tags'] = implode(", ", $tags);
        return $shopifyApiReq;
    }

    public function updateAndCacheMetafields ($isCreate = false) {
        /** @var ServerObjectInfo $serverInfo */
        $serverInfo = $this->serverInfos[reset($this->variants)->getId()];
        if ($isCreate) {
            $productCache = $this->apiManager->getProductMetafields($serverInfo->getObject_id(), $this->targetServer);
        } else {
            $productCache = json_decode($serverInfo->getMetafields_json(), true)['product'];
            $productMetafields = $this->shopifyApiReq['metafields'];
            foreach ($productMetafields as $metafield) {
                # Check if the metafield is changed
                $changedMetafield = $this->getMetafieldChanged($metafield, $this->metafields['product']);

                if (isset($changedMetafield)) {
                    # Update metafield
                    $newProductCacheEl = $this->apiManager->updateProductMetafield($changedMetafield, $serverInfo->getObject_id(), $this->targetServer);
                    foreach ($productCache as $key => $value) {
                        if ($value["key"] === $newProductCacheEl["key"]){
                            $productCache[$key] = $newProductCacheEl;
                            break;
                        }
                    }
                } elseif (!$this->isMetafieldInTarget($metafield['key'], $productCache)) {
                    # Create metafield
                    $productCacheCreate = $this->apiManager->createProductMetafield($metafield, $serverInfo->getObject_id(), $this->targetServer);
                    if ($productCacheCreate) {
                        $productCache[] = $productCacheCreate;
                    }
                }
            }
        }
        $productCache = $this->trimDeletedMetafields($productCache, $serverInfo);

        /** @var Product $variant */
        foreach ($this->variants as $variant) {
            $this->updateAndCacheVariant($productCache, $variant, $isCreate);
        }
    }

    protected function updateImagesCache (int $varId, array $apiResponse) {
        /** @var Product $variation */
        $variation = $this->variants[$varId];
        $variation->setExportServers($this->getImagesUpdatedServerInfosProduct($variation, $apiResponse));
        $variation->update(true);
    }

    protected function getVariantByShopifyVariantId ($shopifyVarId) {
        /**
         * @var int $varId
         * @var ServerObjectInfo $serverInfo
         */
        foreach ($this->serverInfos as $varId => $serverInfo) {
            if ($serverInfo->getVariant_id() == $shopifyVarId) {
                return $this->variants[$varId];
            }
        }
        return null;
    }

    protected function getMetafieldChanged ($newMetafield, $targetMetafields) {
        if (is_array($targetMetafields) && count($targetMetafields) > 0) {
            foreach ($targetMetafields as $metafield) {
                if ($metafield["key"] === $newMetafield["key"] && $metafield['value'] !== $newMetafield['value']) {
                    $newMetafield['id'] = $metafield['id'];
                    return $newMetafield;
                }
            }
        }
        return null;
    }

    protected function updateAndCacheVariant ($prodCache, Product $productVar, bool $isCreate = false) {
        /** @var ServerObjectInfo $serverInfo */
        $serverInfo = $this->serverInfos[$productVar->getId()];

        if ($isCreate) {
            # We are working with a previous cached product
            # Step done only if it's the first time we create the product
            # Case made for performance improvements
            $varCache = $this->apiManager->getProductVariantMetafields($serverInfo->getObject_id(), $serverInfo->getVariant_id(), $this->targetServer);
        } else {
            $varCache = json_decode($serverInfo->getMetafields_json(), true)['variant'];
            $metafields = $this->getVariantFromApiReq($productVar->getSku())['metafields'];
            if(is_array($metafields) && count($metafields)) {
                foreach ($metafields as $metafield) {
                    $changedMetafield = $this->getMetafieldChanged($metafield, $this->metafields['variants'][$productVar->getId()]);
                    if (!isset($varCache)) {
                        $varCache = [];
                    }
                    if (isset($changedMetafield)) {
                        # Update metafield
                        $updatedMetafieldCache = $this->apiManager->updateProductVariantMetafield($changedMetafield, $serverInfo->getObject_id(), $serverInfo->getVariant_id(), $this->targetServer);
                        foreach ($varCache as $key => $value) {
                            if ($value["key"] === $updatedMetafieldCache["key"]){
                                $varCache[$key] = $updatedMetafieldCache;
                                break;
                            }
                        }
                    } elseif (!$this->isMetafieldInTarget($metafield['key'], $varCache)) {
                        # Create metafield
                        $resultCreate = $this->apiManager->createProductVariantMetafield($metafield, $serverInfo->getObject_id(), $serverInfo->getVariant_id(), $this->targetServer);
                        if ($resultCreate) {
                            $varCache[] = $resultCreate;
                        }
                    }
                }
            }
            $varCache = $this->trimDeletedMetafields($varCache, $this->serverInfos[$productVar->getId()], $productVar->getSku());
        }
        
        $productVar->setExportServers($this->getMetafieldUpdatedServerInfosProduct($productVar, $varCache, $prodCache));
        $productVar->update(true);
        
    }

    protected function trimDeletedMetafields(array $metaCaches, ServerObjectInfo $objectInfo, $varSku = null) {
        if (isset($varSku)) {
            # Trim a variant's metafields
            $metafields = $this->getVariantFromApiReq($varSku)['metafields'];
        } else {
            # Trim general product's metafields
            $metafields = $this->shopifyApiReq['metafields'];
        }
        if (is_array($metafields) && count($metafields) > 0 && count($metaCaches) > 0) {
            foreach ($metaCaches as $key => $metaCache) {
                if ($metaCache['key'] && !$this->isMetafieldInTarget($metaCache['key'], $metafields)) {
                    if (isset($varSku)) {
                        $result = $this->apiManager->deleteProductVariantMetafield($metaCache['id'], $objectInfo->getObject_id(), $objectInfo->getVariant_id(), $this->targetServer);
                    } else {
                        $result = $this->apiManager->deleteProductMetafield($metaCache['id'], $objectInfo->getObject_id(), $this->targetServer);
                    }
                    Logger::log('RESPONSE DELETE');
                    Logger::log(print_r($result, true));
                    Logger::log(print_r($key, true));
                    Logger::log($key);
                    Logger::log(print_r($metaCaches[$key], true));
                    # IF delete is successful, remove it from cache as well
                    if (is_array($result) && count($result) === 0) {
                        unset($metaCaches[$key]);
                    }
                }
            }
        }
        return $metaCaches;
    }

    protected function getMetafieldUpdatedServerInfosProduct (Product $variant, array $varCache, array $prodCache) {
        $exportServers = $variant->getExportServers();
        /** @var ServerObjectInfo $exportServer */
        foreach ($exportServers as $exportServer) {
            if ($exportServer->getServer()->getId() === $this->targetServer->getId()) {
                Logger::warn('METAFIELDS JSON');
                $exportServer->setMetafields_json(json_encode([
                        'product' => ($prodCache),
                        'variant' => ($varCache)
                ]));
                Logger::warn($exportServer->getMetafields_json());
                break;
            }
        }
        return $exportServers;
    }

    protected function getImagesUpdatedServerInfosProduct (Product $variant, array $imagesCache) {
        $exportServers = $variant->getExportServers();
        /** @var ServerObjectInfo $exportServer */
        foreach ($exportServers as $exportServer) {
            if ($exportServer->getServer()->getId() === $this->targetServer->getId()) {
                Logger::warn('IMAGES JSON');
                $exportServer->setImages_json(json_encode($imagesCache));
                $exportServer->setImages_sync(true);
                Logger::warn($exportServer->getImages_json());
                break;
            }
        }
        return $exportServers;
    }

    protected function stripMetafields ($apiReq) {
        unset($apiReq['metafields']);
        if (is_array($apiReq) && count($apiReq)) {
            foreach ($apiReq['variants'] as &$variant) {
                unset($variant['metafields']);
            }
        }
        return $apiReq;
    }

    /** Function prepared for */
    protected function removeMetafieldsFromApiReq ($apiReq) {
        $apiReqProdMetafields = $apiReq['metafields'];
        # Parse general product metafields
        foreach ($apiReqProdMetafields as $key => $metafield) {
            if($this->isMetafieldInTarget($metafield['key'], $this->metafields['product']) === true) {
                $this->updateMetafields['product'][] = $metafield;
                unset($apiReq['metafields'][$key]);
            }
        }
        # Parse variant specific metafields
        foreach ($apiReq['variants'] as $pKey => $variant) {
            $varMetafields = &$variant['metafields'];
            $varId = $variant['id'];
            if ($varId && $varMetafields && count($varMetafields)) {
                $this->updateMetafields['variants'] += [$varId => []];
                foreach ($varMetafields as $cKey => $metafield) {
                    if ($this->metafields['variants'][$varId] && $this->isMetafieldInTarget($metafield['key'], $this->metafields['variants'][$varId]) === true) {
                        $this->updateMetafields['variants'][$varId][] = $metafield;
                        unset($apiReq[$pKey][$cKey]);
                    }
                }
            }
        }
        return $apiReq;
    }

    protected function isMetafieldInTarget ($metafieldKey, array $targetMetafields) : bool {
        if(count($targetMetafields)) {
            foreach ($targetMetafields as $metafield) {
                if ($metafield['key'] === $metafieldKey) {
                    return true;
                }
            }
        }
        return false;
    }

    public function updateVariantsInventories () : void {
        /** @var Product $variant */
        foreach($this->variants as $variant) {
            /** @var ServerObjectInfo $serverInfo */
            $serverInfo = $this->serverInfos[$variant->getId()];
            $preparedVar = $this->getVariantFromApiReq($variant->getSku());
            $inventoryJson = json_decode($serverInfo->getInventory_json(), true);

            if ($preparedVar['quantity'] != 0) {
                $payload = [
                        'inventory_item_id' => $inventoryJson['inventory_item_id'],
                        'location_id' => $inventoryJson['location_id'],
                        'available' => $preparedVar['quantity']
                ];
                $response = $this->apiManager->updateInventoryInfo($payload, $this->targetServer);
                $variant->setExportServers($this->getUpdatedServerInfosProduct($variant, [$response]));
                $variant->update(true);
            }
        }
    }

    public function updateInventoryApiResponse () : void {
        $inventoryIds = $this->prepareInventoryItemIds();
        $inventoryJsonList = $this->apiManager->getInventoryInfo([
                'inventory_item_ids' => implode(',', $inventoryIds)
        ], $this->targetServer);
        /** @var Product $variant */
        if (count($inventoryJsonList) > 0) {
            foreach ($this->variants as $variant) {
                /** @var ServerObjectInfo $serverInfo */
                $variant->setExportServers($this->getUpdatedServerInfosProduct($variant, $inventoryJsonList));
                $variant->update(true);
            }
        } else {
            Logger::warn('NO INVENTORY LEVELS FOR IDS: ' . implode(',', $inventoryIds));
        }
    }

    protected function getAllMetafields () {
        $metafields = [
                'product' => [],
                'variants' => []
        ];
        /**
         * @var int $varId
         * @var ServerObjectInfo $serverInfo
         */
        foreach ($this->serverInfos as $varId => $serverInfo) {
            $metafieldJson = $serverInfo->getMetafields_json();
            $metafieldJson = json_decode($metafieldJson, true);
            /** If it's the first time going through the variants since the
             *  product metafields are the same inside every variant json
             *  => do it only once per variants read
             */
            if (count($metafields['product']) === 0 && $metafieldJson['product'] && count($metafieldJson['product'])) {
                foreach ($metafieldJson['product'] as $metafield) {
                    $metafields['product'][] = $metafield;
                }
            }
            if ($metafieldJson['variant'] && count($metafieldJson['variant'])) {
                $metafields['variants'] += [ $varId => [] ];
                foreach ($metafieldJson['variant'] as $metafield) {
                    $metafields['variants'][$varId][] = $metafield;
                }
            }
        }
        return $metafields;
    }

    protected function getQuantityFieldServerMapping () {
        /** @var Fieldcollection\Data\FieldMapping $fieldMap */
        foreach ($this->targetServer->getFieldsMap() as $fieldMap) {
            if ($fieldMap->getServerField() === 'variants.quantity') {
                return $fieldMap->getObjectField();
            }
        }
        return null;
    }

    protected function getUpdatedServerInfosProduct (Product $variant, array $json) : Fieldcollection {
        $exportServers = $variant->getExportServers();
        /** @var ServerObjectInfo $exportServer */
        foreach ($exportServers as $exportServer) {
            if ($exportServer->getServer()->getId() === $this->targetServer->getId()) {
                $exportServer->setInventory_json($this->getInventoryJsonByInventoryId($exportServer->getInventory_id(), $json));
                break;
            }
        }
        return $exportServers;
    }

    protected function getInventoryJsonByInventoryId ($inventoryId, array $jsonArray) : string {
        foreach ($jsonArray as $json) {
            if ($json['inventory_item_id'] === $inventoryId) {
                return json_encode($json);
            }
        }
        return '';
    }

    protected function prepareInventoryItemIds () {
        $inventoryIds = [];
        /** @var ServerObjectInfo $serverInfo */
        foreach ($this->serverInfos as $key => $serverInfo) {
            $inventoryIds[] = $serverInfo->getInventory_id();
        }
        return $inventoryIds;
    }

    /**
     * Get Server Info by variant
     * @param Product $variant
     * @return null|ServerObjectInfo
     */
    protected function getServerInfoByVariant (Product $variant) {
        /** @var ServerObjectInfo $exportServer */
        foreach ($variant->getExportServers() as $exportServer) {
            if ($exportServer->getServer()->getId() === $this->targetServer->getId() && $exportServer->getExport()){
                return $exportServer;
            }
        }
        return null;
    }

    protected function getVariantFromApiReq ($sku) {
        foreach ($this->shopifyApiReq['variants'] as $variant) {
            Logger::warn(json_encode($variant));
            if ($variant['sku'] == $sku) {
                return $variant;
            }
        }
        return null;
    }

    protected function buildCustomModelInfo (Product\Listing $variants) : void {
        $this->variants = [];
        $this->serverInfos = [];

        $variants = $variants->getItems(0, $variants->getCount());
        /** @var Product $variant */
        foreach ($variants as $variant) {
            $serverInfo = $this->getServerInfoByVariant($variant);
            $lastHook = $serverInfo->getLastSyncHook();
            if (isset($lastHook) && !empty($lastHook) && $this->hook && $lastHook !== $variant->{'get'.ucfirst($this->hook)}()) {
                continue;
            }
            $this->variants += [
                    $variant->getId() => $variant
            ];

            if ($serverInfo !== null) {
                $this->serverInfos += [
                        $variant->getId() => $serverInfo
                ];
            }
        }
    }

}

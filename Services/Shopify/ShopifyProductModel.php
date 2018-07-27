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
     * Metafields parsed
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
     * @var ServerObjectInfo
     */
    protected $serverInfos = [];

    public function __construct (Product\Listing $variants, $shopifyApiReq, $shopifyModel, $targetServer) {
        $this->rawVariants = $variants;
        $this->shopifyApiReq = $shopifyApiReq;
        $this->shopifyModel = $shopifyModel;
        $this->targetServer = $targetServer;
        $this->apiManager = (new ShopifyProductAPIManager());
        $this->buildCustomModelInfo($variants);
        $this->metafields = $this->getAllMetafields();
    }

    public function updateShopifyResponse (array $shopifyModel) {
        if (is_array($shopifyModel)) {
            $this->shopifyModel = $shopifyModel;
        }
        $this->buildCustomModelInfo($this->rawVariants);
    }

    public function getParsedShopifyApiRequest ($isCreate = true) {
        $cpyShopifyApiReq = $this->shopifyApiReq;
        return $isCreate ?
                $this->removeMetafieldsFromApiReq($cpyShopifyApiReq) :
                $this->stripMetafields($cpyShopifyApiReq);
    }

    public function updateAndCacheMetafields ($isCreate = false) {
        /** @var ServerObjectInfo $serverInfo */
        $serverInfo = $this->serverInfos[reset($this->variants)->getId()];
        if ($isCreate) {
            $productCache = $this->apiManager->getProductMetafields($serverInfo->getObject_id(), $this->targetServer);
        } else {
            $productCache = [];
            $productMetafields = $this->shopifyApiReq['metafields'];
            foreach ($productMetafields as $metafield) {
                $metafieldCache = $this->apiManager->createProductMetafield($metafield, $serverInfo->getObject_id(), $this->targetServer);
                $productCache += $metafieldCache;
            }
        }
        /** @var Product $variant */
        foreach ($this->variants as $variant) {
            $this->updateAndCacheVariant($productCache, $variant);
        }
    }

    protected function updateAndCacheVariant ($prodCache, Product $productVar) {
        /** @var ServerObjectInfo $serverInfo */
        $serverInfo = $this->serverInfos[$productVar->getId()];

        if (isset($prodCache)) {
            $varCache = $this->apiManager->getProductVariantMetafields($serverInfo->getObject_id(), $serverInfo->getVariant_id(), $this->targetServer);
        } else {
            $varCache = [];
            $metafields = $this->getVariantFromApiReq($productVar->getSku())['metafields'];
            if($metafields && count($metafields)) {
                foreach ($metafields as $metafield) {
                    $metafieldCache = $this->apiManager->createProductVariantMetafield($metafield, $serverInfo->getObject_id(), $serverInfo->getVariant_id(), $this->targetServer);
                    $varCache += $metafieldCache;
                }
            }
        }
        try{
            $productVar->setExportServers($this->getUpdatedServerInfosProduct($variant, [$response]));
            $productVar->update(true);
        } catch (\Exception $e) {
            Logger::warn('COULD NOT SAVE PRODUCT WHILE UPDATING QUANTITY ID: ' . $variant->getId() . ' ' . $e->getMessage());
        }
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
                $this->updateMetafields['product'] += $metafield;
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
                        $this->updateMetafields['variants'][$varId] += $metafield;
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
            Logger::log('WOWWW');
            Logger::log(json_encode($preparedVar));
            $inventoryJson = json_decode($serverInfo->getInventory_json(), true);

            if ($preparedVar['quantity'] != 0) {
                $payload = [
                        'inventory_item_id' => $inventoryJson['inventory_item_id'],
                        'location_id' => $inventoryJson['location_id'],
                        'available' => $preparedVar['quantity']
                ];
                $response = $this->apiManager->updateInventoryInfo($payload, $this->targetServer);
                try{
                    $variant->setExportServers($this->getUpdatedServerInfosProduct($variant, [$response]));
                    $variant->update(true);
                } catch (\Exception $e) {
                    Logger::warn('COULD NOT SAVE PRODUCT WHILE UPDATING QUANTITY ID: ' . $variant->getId() . ' ' . $e->getMessage());
                }
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
                try{
                    $variant->setExportServers($this->getUpdatedServerInfosProduct($variant, $inventoryJsonList));
                    $variant->update(true);
                } catch (\Exception $e) {
                    Logger::warn('COULD NOT SAVE PRODUCT ID: ' . $variant->getId());
                }
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
            $metafieldJson = json_decode($metafieldJson);
            /** If it's the first time going through the variants since the
             *  product metafields are the same inside every variant json
             */
            if (count($metafields['product']) === 0 && $metafieldJson->product && count($metafieldJson->product)) {
                foreach ($metafieldJson->product as $metafield) {
                    $metafields['product'] += $metafield;
                }
            }
            if ($metafieldJson->variant && count($metafieldJson->variant)) {
                $metafields['variants'] += [ $varId => [] ];
                foreach ($metafieldJson->variant as $metafield) {
                    $metafields['variants'][$varId] += $metafield;
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
                Logger::warn('INVENTORY JSON');
                $exportServer->setInventory_json($this->getInventoryJsonByInventoryId($exportServer->getInventory_id(), $json));
                Logger::warn($exportServer->getInventory_json());
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

            $this->variants += [
                    $variant->getId() => $variant
            ];

            $serverInfo = $this->getServerInfoByVariant($variant);
            if ($serverInfo !== null) {
                $this->serverInfos += [
                        $variant->getId() => $serverInfo
                ];
            }
        }
    }

}
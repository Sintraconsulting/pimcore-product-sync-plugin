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
        $this->shopifyApiReq = $shopifyApiReq;
        $this->shopifyModel = $shopifyModel;
        $this->targetServer = $targetServer;
        $this->apiManager = (new ShopifyProductAPIManager());
        $this->buildCustomModelInfo($variants);
    }

    public function updateShopifyResponse (array $shopifyModel) {
        if (is_array($shopifyModel)) {
            $this->shopifyModel = $shopifyModel;
        }
    }

    public function getParsedShopifyApiRequest () {
        $cpyShopifyApiReq = $this->shopifyApiReq;
        unset($cpyShopifyApiReq['metafields']);
        foreach ($cpyShopifyApiReq['variants'] as &$variant) {
            unset($variant['metafields']);
        }
        return $cpyShopifyApiReq;
    }

    public function updateVariantsInventories ($firstUpdate = false) : void {
        /** @var Product $variant */
        foreach($this->variants as $variant) {
            /** @var ServerObjectInfo $serverInfo */
            $serverInfo = $this->serverInfos[$variant->getId()];
            $preparedVar = $this->getVariantFromApiReq($serverInfo->getVariant_id());
            Logger::warn('PREPARED VAR');
            Logger::warn(print_r($preparedVar, true));
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

    protected function getVariantFromApiReq ($varId) {
        foreach ($this->shopifyApiReq['variants'] as $variant) {
            Logger::warn(json_encode($variant));
            if ($variant['id'] == $varId) {
                return $variant;
            }
        }
        return null;
    }

    protected function buildCustomModelInfo (Product\Listing $variants) : void {
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
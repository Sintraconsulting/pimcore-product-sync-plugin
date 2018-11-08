<?php

namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributesAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributeMediaGalleryAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ConfigurableProductLinkAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;
use SintraPimcoreBundle\Utils\GeneralUtils;

class Mage2ProductService extends BaseMagento2Service implements InterfaceService {

    /**
     * Return Product to export with its variants
     * 
     * @param $objectId
     * @param $classname
     * @return Product\Listing
     */
    protected function getObjectsToExport($objectId, $classname) {
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\" . $classname . "\\Listing");
        $listing = $listingClass->newInstance();

        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT]);
        $listing->setCondition("oo_id = " . $listing->quote($objectId) . " OR o_parentId = " . $listing->quote($objectId));
        $listing->setOrderKey(array('o_type', 'oo_id'));
        $listing->setOrder(array('asc', 'asc'));

        return $listing;
    }

    /**
     * Given a product id, retrieve the product and its variants.
     * Create or update the product in a specific Magento2 server and
     * attach variants to it in case of configurable product.
     * 
     * If all the previous operations are completed succesfully,
     * update product's synchronization info.
     * 
     * @param $productId
     * @param TargetServer $targetServer
     * @return mixed|void
     */
    public function export($productId, TargetServer $targetServer) {

        $dataObjects = $this->getObjectsToExport($productId, "Product");
        $dataObject = $dataObjects->current();

        if ($dataObject instanceof Product) {

            $result = $this->createOrUpdateProduct($dataObject, $targetServer);
            Logger::info("UPLOADED PRODUCT: " . $result->__toString());

            if ($dataObject->getType_id() === "configurable") {
                $parentId = $result["id"];
                $this->createVariantsForConfigurableProduct($dataObjects, $targetServer, $parentId);
            }

            $this->setSyncObject($dataObject, $result, $targetServer);
        }
    }

    /**
     * Check the existance of the product in the Magento2 server by the sku field.
     * Create or update the product depending on the previous check.
     * If the product is a variant, attach it to the configurable object.
     * 
     * @param Product $dataObject the product to synchronize
     * @param TargetServer $targetServer the server in which the product must be synchronize
     * @param bool $isVariant flag that specify if the product is a variant or not
     * @return mixed the API result
     */
    private function createOrUpdateProduct(Product $dataObject, TargetServer $targetServer, $isVariant = false) {
        $ecommObject = array();

        $sku = $dataObject->getSku();
        $search = Mage2ProductAPIManager::searchProducts($targetServer, "sku", $sku);

        if ($search["totalCount"] === 0) {
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName(), true);
            Logger::info("MAGENTO CR PRODUCT: " . json_encode($ecommObject));

            $result = Mage2ProductAPIManager::createEntity($ecommObject, $targetServer);
        } else {
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName());
            Logger::info("MAGENTO UP PRODUCT: " . json_encode($ecommObject));

            $result = Mage2ProductAPIManager::updateEntity($sku, $ecommObject, $targetServer);
        }

        $this->manageProductImages($dataObject, $targetServer);

        if ($isVariant) {
            $parentObject = $dataObject->getParent();
            ConfigurableProductLinkAPIManager::addChildToProduct($parentObject->getSku(), $sku, $targetServer);
        }

        return $result;
    }

    /**
     * In case of configurable product, create or update the product variants
     * 
     * @param \Pimcore\Model\DataObject\Product\Listing $dataObjects listing of product and its variants
     * @param TargetServer $targetServer the server in which variants must be synchronized
     * @param type $parentId the configurable product id on the server
     */
    private function createVariantsForConfigurableProduct(Product\Listing $dataObjects, TargetServer $targetServer, $parentId) {
        foreach ($dataObjects->getObjects() as $dataObject) {
            $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

            if ($dataObject instanceof Product && $dataObject->getType() === AbstractObject::OBJECT_TYPE_VARIANT && $serverObjectInfo->getExport() && $serverObjectInfo->getComplete()) {
                $variant = $this->createOrUpdateProduct($dataObject, $targetServer, true);
                Logger::info("UPLOADED VARIANT: " . $variant->__toString());

                $this->setSyncObject($dataObject, $variant, $targetServer, $parentId);
            }
        }
    }

    /**
     * Mapping for Object export
     * It builds the API array for communcation with object endpoint
     * 
     * @param $ecommObject the object to fill for the API call
     * @param $fieldMap the field map between Pimcore and external server
     * @param $fieldsDepth tree structure of the field in the API array
     * @param $language the active language
     * @param Product $dataSource the object to export
     * @param TargetServer $server the external server
     * @return array the API array
     * @throws \Exception
     */
    protected function mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {

        $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);

        // End of recursion
        if (count($fieldsDepth) == 1) {
            return $this->mapServerField($ecommObject, $fieldValue, $fieldsDepth[0]);
        }

        $parentDepth = array_shift($fieldsDepth);
        $apiField = $fieldsDepth[0];

        /**
         * End of recursion with custom_attributes
         */
        if ($parentDepth == 'custom_attributes') {
            $this->extractCustomAttribute($ecommObject, $apiField, $fieldValue);
            return $ecommObject;
        }

        /**
         * End of recursion with configurable_product_options
         * For the configurable product, we must create the configurable options.
         * For a single variant, we should pass the configuration as a custom attribute.
         */
        if ($parentDepth == 'configurable_product_options') {

            if ($dataSource->getType() === AbstractObject::OBJECT_TYPE_OBJECT) {
                $this->extractConfigurableProductOptions($ecommObject, $apiField, $fieldMap, $language, $dataSource, $server);
            } else {
                $this->extractCustomAttribute($ecommObject, $apiField, $fieldValue);
            }

            return $ecommObject;
        }

        /**
         * Recursion level > 1
         */
        $ecommObject[$parentDepth] = $this->mapServerMultipleField($ecommObject[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
        return $ecommObject;
    }

    /**
     * Get the attribute id given the attribute name.
     * 
     * Then, get all the variants for the configurable product
     * and get the configuration for each of them.
     * 
     * @param array $ecommObject the API object
     * @param String $apiField the attribute name
     * @param \Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping $fieldMap the field mapping 
     * @param String $language the selected language
     * @param Product $dataSource the configurable product
     * @param TargetServer $server the server in which the product must be synchronized.
     */
    private function extractConfigurableProductOptions(&$ecommObject, $apiField, $fieldMap, $language, Product $dataSource, TargetServer $server) {
        $productAttribute = ProductAttributesAPIManager::getEntityByKey($apiField, $server);

        $productOption = array(
            "attribute_id" => $productAttribute->getAttributeId(),
            "label" => $productAttribute->getDefaultFrontendLabel()
        );

        $values = [];
        foreach ($dataSource->getChildren(array(AbstractObject::OBJECT_TYPE_VARIANT)) as $product) {
            $fieldValue = $this->getObjectField($fieldMap, $language, $product);

            if ($fieldValue != null && !in_array($fieldValue, $values)) {
                $values[] = $fieldValue;
            }
        }

        if (sizeof($values) > 0) {
            foreach ($values as $value) {
                $productOption["values"][] = array(
                    "value_index" => $value
                );
            }

            $ecommObject["extension_attributes"]["configurable_product_options"][] = $productOption;
        }
    }

    private function manageProductImages(Product $dataObject, TargetServer $targetServer) {
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        if (!$serverObjectInfo->getImages_sync()) {
            $imagesData = array();

            $savedImagesData = json_decode($serverObjectInfo->getImages_json(), true);
            $imagesInfo = GeneralUtils::getObjectImagesInfo($dataObject);

            foreach ($imagesInfo as $position => $imageInfo) {
                $image = $imageInfo->getImage();

                $index = array_search($image->getId(), array_column($savedImagesData, "id"));

                if (!$index) {
                    $this->createImageOnServer($imagesData, $dataObject, $image, $position, $targetServer);
                } else {
                    $savedImage = $savedImagesData[$index];
                    $entryId = $savedImage["server_id"];
                    $serverImage = ProductAttributeMediaGalleryAPIManager::getProductEntry($dataObject->getSku(), $entryId, $targetServer);
                    if (!$serverImage) {
                        $this->createImageOnServer($imagesData, $dataObject, $image, $position, $targetServer);
                    } else {
                        if ($savedImage["position"] != $position || $savedImage["hash"] != $image->getFileSize()) {
                            $this->updateImageOnServer($imagesData, $dataObject, $image, $position, $entryId, $targetServer);
                        }
                    }

                    unset($savedImagesData[$index]);
                }
                
                $this->cacheImageData($imagesData, $image, $position, $result);
            }
            
            foreach ($savedImagesData as $savedImage) {
                $entryId = $savedImage["server_id"];
                ProductAttributeMediaGalleryAPIManager::deleteProductEntry($dataObject->getSku(), $entryId, $targetServer);
            }

            $this->syncImagesData($dataObject, $targetServer, $imagesData);
        }
    }

    private function createEntryAPIObject(Image $image, $position) {
        $title = $image->getMetadata("title");
        $imageBinaryData = base64_encode(file_get_contents($image->getFileSystemPath()));

        $entry = array(
            "media_type" => "image",
            "disabled" => false,
            "position" => $position,
            "label" => $title !== null && !empty($title) ? $title : $image->getFilename(),
            "types" => $position === 0 ? array("swatch_image", "image", "small_image", "thumbnail") : array(),
            "content" => array(
                "type" => $image->getMimetype(),
                "name" => $image->getFilename(),
                "base64_encoded_data" => $imageBinaryData
            )
        );

        return $entry;
    }

    private function createImageOnServer(&$imagesData, Product $dataObject, Image $image, $position, TargetServer $targetServer) {
        $entry = $this->createEntryAPIObject($image, $position);
        $result = ProductAttributeMediaGalleryAPIManager::addEntryToProduct($dataObject->getSku(), $entry, $targetServer);
    }

    private function updateImageOnServer(&$imagesData, Product $dataObject, Image $image, $position, $entryId, TargetServer $targetServer) {
        $entry = $this->createEntryAPIObject($image, $position);
        $entry["id"] = $entryId;
        $result = ProductAttributeMediaGalleryAPIManager::updateProductEntry($dataObject->getSku(), $entryId, $entry, $targetServer);
    }

    private function cacheImageData(&$imagesData, Image $image, $position, $result) {
        if (!is_array($result) || !array_key_exists("ApiException", $result)) {
            $imagesData[] = array(
                "id" => $image->getId(),
                "server_id" => $result,
                "position" => $position,
                "hash" => $image->getFileSize()
            );
        } else {
            throw new \Exception("ERROR ON IMAGE UPLOAD - IMAGE: " . $result["ApiException"]);
        }
    }

    private function syncImagesData(Product $dataObject, TargetServer $targetServer, array $imagesData) {
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        $serverObjectInfo->setImages_sync(true);
        $serverObjectInfo->setImages_json(json_encode($imagesData));

        $dataObject->update(true);
    }

}

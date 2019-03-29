<?php

namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection\Data\ImageInfo;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributesAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributeMediaGalleryAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ConfigurableProductLinkAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;
use SintraPimcoreBundle\Utils\GeneralUtils;

/**
 * Implement methods for products synchronization on Magento2 servers
 * 
 * @author Sintra Consulting
 */
class Mage2ProductService extends BaseMagento2Service implements InterfaceService {

    /**
     * Return Product to export with its variants
     * 
     * @param $objectId
     * @param $classname
     * @return Product\Listing
     */
    protected function getObjectsToExport($objectId, $classname, TargetServer $server){
        $classDef = ClassDefinition::getByName($classname);
        $classId = $classDef->getId();
        $fieldCollName = $classDef->getFieldDefinition('exportServers')->getAllowedTypes()[0];
        $fieldCollectionTable = 'object_collection_' . $fieldCollName . '_' .$classId;
        
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\" . $classname . "\\Listing");
        $listing = $listingClass->newInstance();

        $condition = "(oo_id = " . $listing->quote($objectId) . " OR o_parentId = " . $listing->quote($objectId).")";
        $condition .= " AND oo_id IN (SELECT sourceid FROM dependencies INNER JOIN $fieldCollectionTable ON sourceid = o_id"
                . " WHERE targetid = '".$server->getId()."' AND name = '".$server->getKey()."' AND export = 1)";
        
        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT]);
        $listing->setCondition($condition);
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

        $dataObjects = $this->getObjectsToExport($productId, "Product", $targetServer);
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
     * 
     * After product creation, attach images to the product.
     * 
     * If the product is a variant, attach it to the configurable object.
     * 
     * @param Product $dataObject the product to synchronize
     * @param TargetServer $targetServer the server in which the product must be synchronized
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

        if(method_exists($dataObject, "getImages")){
            $this->synchronizeProductImages($dataObject, $targetServer);
        }

        if ($isVariant) {
            $parentObject = $dataObject->getParent();
            ConfigurableProductLinkAPIManager::addChildToProduct($parentObject->getSku(), $sku, $targetServer);
        }

        return $result;
    }
    
    public function toEcomm(&$ecommObject, $dataObject, TargetServer $targetServer, $classname, bool $isNew = false) {
        parent::toEcomm($ecommObject, $dataObject, $targetServer, $classname, $isNew);
        
        /**
         * In a general approach, API calls will be referred to the main website
         */
        $ecommObject["extension_attributes"]["website_ids"][] = 1;
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
            
            if($apiField === "category_ids"){
                $fieldValue = $this->extractCategoryIds($fieldValue, $server);
            }
            
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
     * 
     * @param Concrete[] $categories
     * @param TargetServer $targetServer
     */
    private function extractCategoryIds($categories, TargetServer $targetServer){
        $categoryIds = [];
        foreach ($categories as $category) {
            $serverObjectInfo = GeneralUtils::getServerObjectInfo($category, $targetServer);
            $categoryIds[] = $serverObjectInfo->getObject_id();
        }
        
        return $categoryIds;
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

    /**
     * If product images has changed, synchronize them in the server.
     * In order to check if a single image is unchanged or if it must be created, updated or deleted 
     * get the previuolsy stored image informations and compare them with current images.
     * 
     * If an error occours on images synchronization, store the correctly synchronized images informations
     * and the previous ones that couldn't be processed because of the error.
     * 
     * So that, before every synchronization flow start, stored images informations
     * will match the physically existent images on the server.
     * 
     * @param Product $dataObject the product to synchronize
     * @param TargetServer $targetServer the server in which the product must be synchronized
     * @throws \Exception
     */
    private function synchronizeProductImages(Product $dataObject, TargetServer $targetServer) {
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        if (!$serverObjectInfo->getImages_sync()) {
            $imagesData = array();

            $imagesJson = $serverObjectInfo->getImages_json();
            $savedImagesData = ($imagesJson != null && !empty($imagesJson)) ? json_decode($imagesJson, true) : array();

            $imagesInfo = GeneralUtils::getObjectImagesInfo($dataObject);

            try {
                $this->synchronizeImagesOnServer($dataObject, $imagesInfo, $savedImagesData, $imagesData, $targetServer);
            } catch (\Exception $e) {
                $this->syncImagesData($dataObject, $targetServer, array_merge($imagesData, $savedImagesData), false);
                throw $e;
            }
        }
    }

    /**
     * Try to synchronize every product image on Pimcore.
     * Then, if previously stored images informations don't match with any of the curren images
     * delete them from the server.
     * 
     * If all these operations end succesfully, store new images informations
     * and mark images as synchronized for the product
     * 
     * @param Product $dataObject the product to synchronize
     * @param ImageInfo[] $imagesInfo product images on Pimcore
     * @param array $savedImagesData previously stored images informations
     * @param array $imagesData current images informations
     * @param TargetServer $targetServer the server in which the product must be synchronized
     */
    private function synchronizeImagesOnServer(Product $dataObject, $imagesInfo, array &$savedImagesData, array &$imagesData, TargetServer $targetServer) {
        foreach ($imagesInfo as $position => $imageInfo) {
            $this->synchronizeImage($dataObject, $imageInfo, $savedImagesData, $imagesData, $position, $targetServer);
        }

        foreach ($savedImagesData as $savedImage) {
            $entryId = $savedImage["server_id"];
            Logger::info("Immagine $entryId rimossa da Pimcore. Rimuovo Immagine");
            ProductAttributeMediaGalleryAPIManager::deleteProductEntry($dataObject->getSku(), $entryId, $targetServer);
        }
        
        $allProductImages = ProductAttributeMediaGalleryAPIManager::getAllProductEntries($dataObject->getSku(), $targetServer);
        foreach ($allProductImages as $productImage) {
            if(array_search($productImage["id"], array_column($imagesData,"server_id")) === FALSE){
                Logger::info("Immagine ".$productImage["id"]." rimossa da Pimcore. Rimuovo Immagine");
                ProductAttributeMediaGalleryAPIManager::deleteProductEntry($dataObject->getSku(), $productImage["id"], $targetServer);
            }
        }

        $this->syncImagesData($dataObject, $targetServer, $imagesData);
    }

    /**
     * Search for current image id in the previously stored images information array.
     * 
     * If the image is not founded, it means that is new and must be created on the server.
     * Else, search for the image on the server by image id.
     * 
     * If the image is not present anymore on the server, it means that it was deleted manually and must be re-created.
     * Else, check if the image has changed and must be updated, of it's unchanged.
     * 
     * if the synchronization ends succesfully, 
     * remove image reference from the previously stored images informations.
     * 
     * @param Product $dataObject the product to synchronize
     * @param ImageInfo $imageInfo product image on Pimcore
     * @param array $savedImagesData previously stored images informations
     * @param array $imagesData current images informations
     * @param int $position position of the image on Pimcore
     * @param TargetServer $targetServer the server in which the product must be synchronized
     */
    private function synchronizeImage(Product $dataObject, ImageInfo $imageInfo, array &$savedImagesData, array &$imagesData, $position, TargetServer $targetServer) {
        $image = $imageInfo->getImage();

        $index = array_search($image->getId(), array_column($savedImagesData, "id"));

        if ($index === false) {
            Logger::info("New Image. Create it on the Server");
            $this->createImageOnServer($imagesData, $dataObject, $image, $position, $targetServer);
        } else {
            $savedImage = $savedImagesData[$index];
            $entryId = $savedImage["server_id"];
            
            $imageExists = ProductAttributeMediaGalleryAPIManager::getProductEntry($dataObject->getSku(), $entryId, $targetServer);
            
            if (!$imageExists) {
                Logger::info("Image with Id $entryId removed from Server. Re-Create it on the Server");
                $this->createImageOnServer($imagesData, $dataObject, $image, $position, $targetServer);
            } else {
                if ($savedImage["position"] != $position || $savedImage["hash"] != $image->getFileSize()) {
                    Logger::info("Image with Id $entryId has changed. Update Image on the Server");
                    $this->updateImageOnServer($imagesData, $dataObject, $image, $position, $entryId, $targetServer);
                } else {
                    Logger::info("Image with Id $entryId has not changed. Skip Image");
                    $this->cacheImageData($imagesData, $image, $position, $entryId);
                }
            }

            array_splice($savedImagesData, $index, 1);
        }
    }

    /**
     * Create an image on the server.
     * If the operation ends succesfully, store image informations
     * 
     * @param array $imagesData current images informations
     * @param Product $dataObject the product to synchronize
     * @param Image $image the current image
     * @param int $position position of the current image on Pimcore
     * @param TargetServer $targetServer the server in which the product must be synchronized
     */
    private function createImageOnServer(array &$imagesData, Product $dataObject, Image $image, $position, TargetServer $targetServer) {
        $entry = $this->createEntryAPIObject($image, $position);
        $result = ProductAttributeMediaGalleryAPIManager::addEntryToProduct($dataObject->getSku(), $entry, $targetServer);
        
        if(!is_array($result) || !array_key_exists("ApiException", $result)){
            $this->cacheImageData($imagesData, $image, $position, $result);
        } else {
            throw new \Exception("ERROR ON IMAGE UPLOAD - IMAGE: " . $result["ApiException"]);
        }
    }

    /**
     * Update an image on the server.
     * If the operation ends succesfully, store image informations
     * 
     * @param array $imagesData current images informations
     * @param Product $dataObject the product to synchronize
     * @param Image $image the current image
     * @param int $position position of the current image on Pimcore
     * @param int $entryId The image Id on the server
     * @param TargetServer $targetServer the server in which the product must be synchronized
     */
    private function updateImageOnServer(array &$imagesData, Product $dataObject, Image $image, $position, $entryId, TargetServer $targetServer) {
        $entry = $this->createEntryAPIObject($image, $position);
        $entry["id"] = $entryId;
        $result = ProductAttributeMediaGalleryAPIManager::updateProductEntry($dataObject->getSku(), $entryId, $entry, $targetServer);
        
        if(!is_array($result) || !array_key_exists("ApiException", $result)){
            $this->cacheImageData($imagesData, $image, $position, $entryId);
        } else {
            throw new \Exception("ERROR ON IMAGE UPLOAD - IMAGE: " . $result["ApiException"]);
        }
    }

    /**
     * Create the object to be used in the API call for synchronization
     * 
     * @param Image $image the current image
     * @param int $position position of the current image on Pimcore
     * @return array $entry the API object
     */
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

    /**
     * If the synchronization API call ends succesfully, save the image informations.
     * Otherwise, throw the error message obtained from the server.
     * 
     * @param array $imagesData current images informations
     * @param Image $image the current image
     * @param int $position position of the current image on Pimcore
     * @param int $entryId The image Id on the server
     * @throws \Exception
     */
    private function cacheImageData(array &$imagesData, Image $image, $position, $entryId) {
        $imagesData[] = array(
            "id" => $image->getId(),
            "server_id" => $entryId,
            "position" => $position,
            "hash" => $image->getFileSize()
        );
    }

    /**
     * Store synchronized images informations.
     * If the whole synchronization has end succesfully, mark images as synchronized for the product
     * 
     * @param Product $dataObject the product to synchronize
     * @param TargetServer $targetServer the server in which the product must be synchronized
     * @param array $imagesData current images informations
     * @param bool $success tells if the whole synchronization has end succesfully
     */
    private function syncImagesData(Product $dataObject, TargetServer $targetServer, array $imagesData, $success = true) {
        $serverObjectInfo = GeneralUtils::getServerObjectInfo($dataObject, $targetServer);

        $serverObjectInfo->setImages_sync($success);
        $serverObjectInfo->setImages_json(json_encode($imagesData));

        $dataObject->update(true);
    }

}

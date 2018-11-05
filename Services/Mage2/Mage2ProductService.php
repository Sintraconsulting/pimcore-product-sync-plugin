<?php

namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributesAPIManager;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;

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
     * @param $productId
     * @param TargetServer $targetServer
     * @return mixed|void
     */
    public function export($productId, TargetServer $targetServer) {
        $ecommObject = array();

        $dataObjects = $this->getObjectsToExport($productId, "Product");

        /** @var Product $dataObject */
        $dataObject = $dataObjects->current();

        $sku = $dataObject->getSku();
        $search = Mage2ProductAPIManager::searchProducts($targetServer, "sku", $sku);

        if ($search["totalCount"] === 0) {
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName(), true);
            Logger::debug("MAGENTO CR PRODUCT: " . json_encode($ecommObject));

            $result = Mage2ProductAPIManager::createEntity($magento2Product, $targetServer);
        } else {
            $this->toEcomm($ecommObject, $dataObject, $targetServer, $dataObject->getClassName());
            Logger::debug("MAGENTO UP PRODUCT: " . json_encode($ecommObject));

            $result = Mage2ProductAPIManager::updateEntity($sku,$magento2Product, $targetServer);
        }
        Logger::debug("UPLOADED PRODUCT: ".$result->__toString());
        
        $this->setSyncObject($dataObject, $result, $targetServer);
    }

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $ecommObject
     * @param Product $dataObject
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $isNew
     */
    public function toEcomm(&$ecommObject, $dataObject, TargetServer $targetServer, $classname, bool $isNew = false) {
        parent::toEcomm($ecommObject, $dataObject, $targetServer, $classname, $isNew);
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

            $ecommObject["configurable_product_options"][] = $productOption;
        }
    }

}

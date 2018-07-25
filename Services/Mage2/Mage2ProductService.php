<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\ApiManager\Mage2\ProductAttributesAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;

class Mage2ProductService extends BaseMagento2Service implements InterfaceService {

    private $configFile = __DIR__ . '/../config/product.json';

    /**
     * Return Product to export with its variants
     * 
     * @param $objectId
     * @param $classname
     * @return Listing
     */
    protected function getObjectsToExport($objectId, $classname){
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\".$classname."\\Listing");
        $listing = $listingClass->newInstance();

        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT,AbstractObject::OBJECT_TYPE_VARIANT]);
        $listing->setCondition("oo_id = ".$listing->quote($objectId). " OR o_parentId = ".$listing->quote($objectId));
        $listing->setOrderKey('oo_id');
        $listing->setOrder('asc');
        
        return $listing;
    }
    
    /**
     * @param $productId
     * @param TargetServer $targetServer
     * @return mixed|void
     */
    public function export ($productId, TargetServer $targetServer) {
        $magento2Product = json_decode(file_get_contents($this->configFile), true)[$targetServer->getKey()];
        
        $dataObjects = $this->getObjectsToExport($productId, "Product");
        
        /** @var Product $dataObject */
        $dataObject = $dataObjects->current();

        $sku = $dataObject->getSku();
        $search = Mage2ProductAPIManager::searchProducts($targetServer,"sku", $sku);

        if($search["totalCount"] === 0){
            //product is new, need to save price
            $this->toEcomm($magento2Product, $dataObjects, $targetServer, $dataObject->getClassName(), true);
            Logger::debug("MAGENTO CR PRODUCT: ".json_encode($magento2Product));

            $result = Mage2ProductAPIManager::createEntity($magento2Product, $targetServer);
        }else{
            //product already exists, we may want to not update prices
            $this->toEcomm($magento2Product, $dataObjects, $targetServer, $dataObject->getClassName(), MagentoConfig::$updateProductPrices);
            Logger::debug("MAGENTO UP PRODUCT: ".json_encode($magento2Product));

            $result = Mage2ProductAPIManager::updateEntity($sku,$magento2Product, $targetServer);
        }
        Logger::debug("UPDATED PRODUCT: ".$result->__toString());

        try {
            $this->setSyncObject($dataObject, $result, $targetServer);
        } catch (\Exception $e) {
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }
    
    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $ecommObject
     * @param Product\Listing $dataObjects
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $updateProductPrices
     */
    public function toEcomm (&$ecommObject, $dataObjects, TargetServer $targetServer, $classname, bool $updateProductPrices = false) {
        parent::toEcomm($ecommObject, $dataObjects, $targetServer, $classname, $updateProductPrices);
        
        if(!$updateProductPrices){
            unset($ecommObject["price"]);
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
     * @param Product\Listing $dataSource the object to export
     * @param TargetServer $server the external server
     * @return array the API array
     * @throws \Exception
     */
    protected function mapServerMultipleField ($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {
        // End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Listing $dataSource */
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            
            return $this->mapServerField($ecommObject, $fieldValue, $apiField);
        }
        
        $parentDepth = array_shift($fieldsDepth);


        /**
         * End of recursion with custom_attributes
         */
        if ($parentDepth == 'custom_attributes') {
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            
            $customValue = [
                    'attribute_code' => $apiField,
                    'value' => $fieldValue
            ];
            $ecommObject[$parentDepth][] = $customValue;
            return $ecommObject;
        }
        
        /**
         * End of recursion with configurable_product_options
         */
        if($parentDepth == 'configurable_product_options'){
            $apiField = $fieldsDepth[0];
            
            $productAttribute = ProductAttributesAPIManager::getEntityByKey($apiField, $server);
            
            Logger::info("PRODUCT ATTRIBUTE: ".print_r($productAttribute,true));
            
            $productOption = array(
                "attribute_id" => $productAttribute["attribute_id"],
                "label" => $productAttribute["default_frontend_label"]
            );
            
            $values = [];
            foreach ($dataSource->getObjects() as $product) {
                $fieldValue = $this->getObjectField($fieldMap, $language, $product);

                if ($fieldValue != null && !in_array($fieldValue, $values)){
                    $values[] = $fieldValue; 
                }
            }
            
            if(sizeof($values) > 0){
                foreach ($values as $value) {
                    $productOption["values"][] = array(
                        "value_index" => $value
                    );
                }
                
                $ecommObject[$parentDepth][] = $productOption;
            }
            
            return $ecommObject;
            
        }

        /**
         * Recursion level > 1
         */
        $ecommObject[$parentDepth] = $this->mapServerMultipleField($ecommObject[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
        return $ecommObject;
        
        
    }
    
    
    
}
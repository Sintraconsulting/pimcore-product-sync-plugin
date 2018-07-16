<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;

class Mage2ProductService extends BaseMagento2Service implements InterfaceService {

    private $configFile = __DIR__ . '/../config/product.json';

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

        $apiManager = Mage2ProductAPIManager::getInstance();
        $sku = $dataObject->getSku();
        $search = $apiManager->searchProducts($targetServer,"sku", $sku);

        if($search["totalCount"] === 0){
            //product is new, need to save price
            $this->toEcomm($magento2Product, $dataObject, $targetServer, true);
            Logger::debug("MAGENTO CR PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->createEntity($magento2Product, $targetServer);
        }else{
            //product already exists, we may want to not update prices
            $this->toEcomm($magento2Product, $dataObject, $targetServer, MagentoConfig::$updateProductPrices);
            Logger::debug("MAGENTO UP PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->updateEntity($sku,$magento2Product, $targetServer);
        }
        Logger::debug("UPDATED PRODUCT: ".$result->__toString());

        try {
            $this->setSyncProducts($dataObject, $result, $targetServer);
        } catch (\Exception $e) {
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * 
     * @param Product $product
     * @param $results
     * @param TargetServer $targetServer
     */
    protected function setSyncProducts ($product, $results, TargetServer $targetServer) {
        $serverObjectInfo = $this->getServerObjectInfo($product, $targetServer);
        $serverObjectInfo->setSync(true);
        $serverObjectInfo->setSync_at($results["updatedAt"]);
        $product->update(true);
    }
    
    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $$ecommObject
     * @param Product\Listing $dataObjects
     * @param TargetServer $targetServer
     * @param bool $updateProductPrices
     */
    public function toEcomm (&$ecommObject, $dataObjects, TargetServer $targetServer, bool $updateProductPrices = false) {
        $exportMap = $targetServer->getExportMap()->getItems();
        $languages = $targetServer->getLanguages();
        
        /** @var FieldMapping $fieldMap */
        foreach ($exportMap as $fieldMap) {

            //get the value of each object field
            $apiField = $fieldMap->getServerField();

            $fieldsDepth = explode('.', $apiField);
            $ecommObject = $this->mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $languages[0], $dataObjects, $targetServer);

        }
        
        if(!$updateProductPrices){
            unset($ecommObject["price"]);
        }

    }
    
    protected function mapServerMultipleField ($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, $server = null) {
        // End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Product\Listing $dataSource */
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
         * Recursion level > 1
         * For now, on magento2 there is no nested field mapping except custom_attributes
         * It should never reach this point with magento2.
         * TODO: image implementation should be developed in the future here for field mapping
         */
        return $this->mapServerMultipleField($ecommObject[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
    }
}
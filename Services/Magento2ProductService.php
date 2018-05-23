<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;
use Pimcore\Logger;

class Magento2ProductService extends BaseMagento2Service implements InterfaceService {

    private $configFile = __DIR__ . '/config/product.json';

    /**
     * @param Product $dataObject
     * @return mixed|void
     */
    public function export ($dataObject) {

        $apiManager = ProductAPIManager::getInstance();
        $sku = $dataObject->getSku();
        $search = $apiManager->searchProducts("sku", $sku);

        if($search["totalCount"] === 0){
            //product is new, need to save price
            $magento2Product = $this->toEcomm($dataObject, true);
            Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->createEntity($magento2Product);
        }else{
            //product already exists, we may want to not update prices
            $magento2Product = $this->toEcomm($dataObject, MagentoConfig::$updateProductPrices);
            Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->updateEntity($sku,$magento2Product);
        }
        Logger::debug("UPDATED PRODUCT: ".$result->__toString());

        $dataObject->setMagento_syncronized(true);
        $dataObject->setMagento_syncronyzed_at($result["updatedAt"]);

        try{
            $dataObject->update(true);
        }
        catch (Exception $e){
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    public function toEcomm ($dataObject, bool $updateProductPrices = false) {
        $magento2Product = (json_decode(file_get_contents($this->configFile), true))['magento2'];

        if(!$updateProductPrices){
            unset($magento2Product["price"]);
        }

        $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            $fieldValue = $dataObject->getValueForFieldName($fieldName);

            $this->mapField($magento2Product, $fieldName, $fieldType, $fieldValue, $dataObject->getClassId());
        }

        return $magento2Product;
    }
}
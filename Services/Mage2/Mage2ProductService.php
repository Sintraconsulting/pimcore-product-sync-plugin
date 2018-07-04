<?php
namespace SintraPimcoreBundle\Services\Mage2;

use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;
use Pimcore\Logger;
use SintraPimcoreBundle\Services\InterfaceService;

class Mage2ProductService extends BaseMagento2Service implements InterfaceService {

    private $configFile = __DIR__ . '/../config/product.json';

    /**
     * @param Product $dataObject
     * @return mixed|void
     */
    public function export ($dataObject) {
        $magento2Product = json_decode(file_get_contents($this->configFile), true)['magento2'];

        $apiManager = Mage2ProductAPIManager::getInstance();
        $sku = $dataObject->getSku();
        $search = $apiManager->searchProducts("sku", $sku);

        if($search["totalCount"] === 0){
            //product is new, need to save price
            $this->toEcomm($magento2Product, $dataObject, true);
            Logger::debug("MAGENTO CR PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->createEntity($magento2Product);
        }else{
            //product already exists, we may want to not update prices
            $this->toEcomm($magento2Product, $dataObject, MagentoConfig::$updateProductPrices);
            Logger::debug("MAGENTO UP PRODUCT: ".json_encode($magento2Product));

            $result = $apiManager->updateEntity($sku,$magento2Product);
        }
        Logger::debug("UPDATED PRODUCT: ".$result->__toString());

        $dataObject->setMagento_sync(true);
        $dataObject->setMagento_sync_at($result["updatedAt"]);

        try{
            $dataObject->update(true);
        }
        catch (Exception $e){
            Logger::notice($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    public function toEcomm (&$ecommObject, $dataObject, bool $updateProductPrices = false) {
        $ecommObject = json_decode(file_get_contents($this->configFile), true)['magento2'];

        if(!$updateProductPrices){
            unset($ecommObject["price"]);
        }

        $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            $fieldValue = $dataObject->getValueForFieldName($fieldName);

            $this->mapField($ecommObject, $fieldName, $fieldType, $fieldValue, $dataObject->getClassId());
        }

        //return $magento2Product;
    }
}
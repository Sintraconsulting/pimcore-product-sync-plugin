<?php

namespace SintraPimcoreBundle\Utils;

use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\Resources\Magento\MagentoConfig;
use SintraPimcoreBundle\Utils\MagentoUtils;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;

/**
 * Utils for mapping Pimcore Product Object to Magento2 Product Object
 *
 * @author Marco Guiducci
 */
class ProductUtils extends MagentoUtils{

    private static $instance;
    
    private $configFile = __DIR__.'/config/product.json';

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function exportToMagento(Product $product){
        $apiManager = ProductAPIManager::getInstance();

        $sku = $product->getSku();
        $search = $apiManager->searchProducts("sku",$sku);

        if($search["totalCount"] === 0){
            //product is new, need to save price
            $magento2Product = $this->toMagento2Product($product, true);
            Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));
        
            $result = $apiManager->createEntity($magento2Product);
        }else{
            //product already exists, we may want to not update prices
            $magento2Product = $this->toMagento2Product($product, MagentoConfig::$updateProductPrices);
            Logger::debug("MAGENTO PRODUCT: ".json_encode($magento2Product));
            
            $result = $apiManager->updateEntity($sku,$magento2Product);
        }
        
        Logger::debug("UPDATED PRODUCT: ".$result->__toString());

        $product->setMagento_syncronized(true);
        $product->setMagento_syncronyzed_at($result["updatedAt"]);
        
        $product->update(true);
    }
    
    private function toMagento2Product(Product $product, $updateProductPrices){
        
        $magento2Product = json_decode(file_get_contents($this->configFile), true);
        
        if(!$updateProductPrices){
            unset($magento2Product["price"]);
        }
        
        $fieldDefinitions = $product->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldName = $fieldDefinition->getName();
            $fieldType = $fieldDefinition->getFieldtype();
            $fieldValue = $product->getValueForFieldName($fieldName);
            
            $this->mapField($magento2Product, $fieldName, $fieldType, $fieldValue, $product->getClassId());
        }
        
        return $magento2Product;
        
    }

}
<?php

namespace Magento2PimcoreBundle\Utils;

use Magento2PimcoreBundle\Utils\MagentoUtils;

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
    
    public function toMagento2Product(Product $product){
        
        $magento2Product = json_decode(file_get_contents($this->configFile), true);
        
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
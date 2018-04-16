<?php

namespace Magento2PimcoreBundle\Import\Resolvers;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\Product;

/**
 * Resolve product by Sku
 *
 * @author Marco Guiducci
 */
class ProductSkuResolver extends AbstractResolver{

    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $columnId = $this->getIdColumn($config);
        
        $sku = $rowData[$columnId];
        $products = new Product\Listing();
        $products->setCondition("sku = ".$products->quote($sku));
        $products->setLimit(1);
        
        $products = $products->load();
        
        if($products){
            $product = $products[0];
        }else{
            $product = new Product();
            $product->setParentId($parentId);
            $product->setSku($sku);
            $product->setPublished(1);
            
            /**
             * set object key to avoid import error
             * will be overridden if "key" is mapped
             * in import column configuration
             */
            $product->setKey($sku);
        }
        
        return $product;
        
    }

}

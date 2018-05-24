<?php

namespace SintraPimcoreBundle\Import\Resolvers\Product;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\Product;

/**
 * Resolve product by Sku
 *
 * @author Marco Guiducci
 */
class SkuResolver extends AbstractResolver{

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
             */
            $keyColumnId = $this->getKeyColumnId($config);
            if(!empty($keyColumnId)){
                $key = $rowData[$keyColumnId];
                $product->setKey($key);
            }else{
                $product->setKey($sku);
            }
        }
        
        return $product;
        
    }
    
    private function getKeyColumnId(\stdClass $config){
        $configArray = json_decode(json_encode($config), true);
        $selectedGridColumns = $configArray["selectedGridColumns"];
        
        $keyColumnId = array_search("key", array_column(array_column($selectedGridColumns, 'attributes'), 'attribute'));
        
        return $keyColumnId;
    }

}

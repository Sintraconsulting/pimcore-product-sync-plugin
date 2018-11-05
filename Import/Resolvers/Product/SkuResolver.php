<?php

namespace SintraPimcoreBundle\Import\Resolvers\Product;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\Product;

/**
 * Resolve product by Sku
 * 
 * name_column_id: optional additional column Id used for product key generation
 *
 * @author Marco Guiducci
 */
class SkuResolver extends AbstractResolver{

    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $params = json_decode($config->resolverSettings->params,true);
        $nameColumnId = $params["name_column_id"];
        
        $columnId = $this->getIdColumn($config);
        
        $sku = trim($rowData[$columnId]);
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
            $product->setPublished(0);
            
            /**
             * Set object key to avoid import error
             * If the "name_column_id" parameter is set, product key is generated
             * combining SKU and the value in the "name_column_id" column
             */
            if($nameColumnId != null && !empty($nameColumnId)){
                $key = trim($rowData[$nameColumnId]);
                $product->setKey(str_replace("/","\\",$sku." - ".$key));
            }else{
                $product->setKey(str_replace("/","\\",$sku));
            }
        }
        
        return $product;
        
    }
    
    private function getColumnId(\stdClass $config, $columnname){
        $configArray = json_decode(json_encode($config), true);
        $selectedGridColumns = $configArray["selectedGridColumns"];
        
        $keyColumnId = array_search($columnname, array_column(array_column($selectedGridColumns, 'attributes'), 'attribute'));
        
        return $keyColumnId;
    }

}

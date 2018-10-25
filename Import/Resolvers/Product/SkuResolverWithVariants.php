<?php

namespace SintraPimcoreBundle\Import\Resolvers\Product;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\AbstractObject;

/**
 * Resolve product by Sku checking for variants
 *
 * @author Marco Guiducci
 */
class SkuResolverWithVariants extends AbstractResolver{

    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $params = json_decode($config->resolverSettings->params,true);
        $nameColumnId = $params["name_column_id"];
        
        $columnId = $this->getIdColumn($config);
                
        $sku = trim($rowData[$columnId]);
        $products = new Product\Listing();
        $products->setCondition("sku = ".$products->quote($sku));
        $products->setObjectTypes([AbstractObject::OBJECT_TYPE_VARIANT, AbstractObject::OBJECT_TYPE_OBJECT]);
        $products->setLimit(1);
        
        $products = $products->load();
        
        if($products){
            $product = $products[0];
        }else{
            $product = new Product();
            $product->setSku($sku);
            $product->setPublished(1);
            
            $typeColumnId = $config->resolverSettings->columnObjectType;
            $type = $rowData[$typeColumnId];
            $product->setType($type);
            
            switch ($type) {
                case AbstractObject::OBJECT_TYPE_OBJECT:
                    $product->setParentId($parentId);
                    break;
                
                case AbstractObject::OBJECT_TYPE_VARIANT:
                    $product->setParentId($this->getProductParentId($config, $rowData));
                    break;

                default:
                    throw new \Exception("SkuResolverWithVariants - ERROR - product type must be 'object' or 'variant'. '$type' given.");
            }
            
            
            
            /**
             * set object key to avoid import error
             */
            if($nameColumnId != null && !empty($nameColumnId)){
                $key = trim($rowData[$nameColumnId]);
                $product->setKey($sku." - ".$key);
            }else{
                $product->setKey($sku);
            }
        }
        
        return $product;
        
    }
    
    private function getColumnId(\stdClass $config, $columnname){
        $configArray = json_decode(json_encode($config), true);
        $selectedGridColumns = $configArray["selectedGridColumns"];
        
        $columnId = array_search($columnname, array_column(array_column($selectedGridColumns, 'attributes'), 'attribute'));
        
        return $columnId;
    }
    
    private function getColumnIdFromlabel(\stdClass $config, $columnname){
        $configArray = json_decode(json_encode($config), true);
        $selectedGridColumns = $configArray["selectedGridColumns"];
        
        $columnId = array_search($columnname, array_column(array_column($selectedGridColumns, 'attributes'), 'label'));
        
        return $columnId;
    }
    
    private function getProductParentId(\stdClass $config, array $rowData){
        $parentColumnId = $this->getColumnIdFromlabel($config, "parent");
        $parent = $rowData[$parentColumnId];
        
        $productParent = Product::getBySku($parent)->current();
        return $productParent->getId();
    }

}

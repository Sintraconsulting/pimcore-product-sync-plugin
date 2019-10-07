<?php

namespace SintraPimcoreBundle\Import\Resolvers\Product;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\AbstractObject;

/**
 * Resolve product by Sku checking for variants
 *
 * name_column_id: optional additional column Id used for product key generation
 * 
 * @author Sintra Consulting
 */
class SkuResolverWithVariants extends AbstractResolver{

    /**
     * Check if the product exists as object or variant.
     * If yes, return the product.
     * If not, the type of the new object is read from a defined column in the CSV.
     * For variants, parent product SKU is read from the "parent" column in the CSV. 
     */
    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $params = json_decode($config->resolverSettings->params,true);
        $nameColumnId = $params["name_column_id"];
        
        $columnId = $this->getIdColumn($config);
                
        $sku = trim($rowData[$columnId]);
        $listing = new Product\Listing();
        $listing->setCondition("sku = ".$listing->quote($sku));
        $listing->setObjectTypes([AbstractObject::OBJECT_TYPE_VARIANT, AbstractObject::OBJECT_TYPE_OBJECT]);
        $listing->setLimit(1);
        
        $products = $listing->load();
        
        if($products){
            $product = $products[0];
        }else{
            $product = new Product();
            $product->setSku($sku);
            $product->setPublished(0);
            
            $typeColumnId = $config->resolverSettings->columnObjectType;
            $type = $rowData[$typeColumnId];
            $product->setType($type);
            
            switch ($type) {
                case AbstractObject::OBJECT_TYPE_OBJECT:
                    $product->setParentId($parentId);
                    break;
                
                case AbstractObject::OBJECT_TYPE_VARIANT:
                    $parentId = $this->getProductParentId($config, $rowData);
                    
                    if($parentId != null){
                        $product->setParentId($parentId);
                    }else{
                        throw new \Exception("SkuResolverWithVariants - ERROR - Parent object not found");
                    }
                    
                    break;

                default:
                    throw new \Exception("SkuResolverWithVariants - ERROR - product type must be 'object' or 'variant'. '$type' given.");
            }
            
            
            
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
    
    
    private function getProductParentId(\stdClass $config, array $rowData){
        $parentColumnId = $this->getColumnIdFromlabel($config, "parent");
        $parent = $rowData[$parentColumnId];
        
        $productParent = Product::getBySku($parent)->current();
        
        if($productParent){
            return $productParent->getId();
        }
        
        return null;
    }
    
    private function getColumnIdFromlabel(\stdClass $config, $columnname){
        $configArray = json_decode(json_encode($config), true);
        $selectedGridColumns = $configArray["selectedGridColumns"];
        
        $columnId = array_search($columnname, array_column(array_column($selectedGridColumns, 'attributes'), 'label'));
        
        return $columnId;
    }

}

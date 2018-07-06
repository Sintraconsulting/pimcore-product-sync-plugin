<?php

namespace SintraPimcoreBundle\EventListener\Shopify;

use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\InterfaceListener;

class ShopifyProductListener extends ShopifyObjectListener implements InterfaceListener{

    /**
     * @param Product $product
     */
    public function preAddAction($dataObject) {
        
    }

    /**
     * @param Product $product
     */
    public function preUpdateAction($product) {
        
    }

    /**
     * @param Product $product
     */
    public function postUpdateAction($product) {
        
//        $product->setShopify_sync(false);
//
//        $product->update(true);
    }

    /**
     * @param Product $product
     */
    public function postDeleteAction($product, $isUnpublished = false) {
        
    }

}

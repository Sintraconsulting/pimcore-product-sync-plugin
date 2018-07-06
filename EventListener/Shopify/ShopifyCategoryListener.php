<?php

namespace SintraPimcoreBundle\EventListener\Shopify;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\EventListener\InterfaceListener;

class ShopifyCategoryListener extends ShopifyObjectListener implements InterfaceListener {

    /**
     * @param Category $product
     */
    public function preAddAction($dataObject) {
        
    }
    
    /**
     * @param Category $category
     */
    public function preUpdateAction($category) {
        
    }

    /**
     * @param Category $category
     */
    public function postUpdateAction($category) {
        
//        $category->setShopify_sync(false);
//
//        $category->update(true);
    }

    /**
     * @param Category $category
     */
    public function postDeleteAction($category, $isUnpublished = false) {

    }

}

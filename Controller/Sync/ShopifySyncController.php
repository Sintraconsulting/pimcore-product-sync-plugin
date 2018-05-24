<?php

namespace SintraPimcoreBundle\Controller\Sync;

use SintraPimcoreBundle\Services\Shopify\ShopifyProductService;
use Pimcore\Model\DataObject\Product\Listing;

class ShopifySyncController extends BaseSyncController {
    protected $ecommerce = 'shopify';

    /**
     * @param int $count
     * @return string
     * @throws \Exception
     */
    public function syncProducts (int $count = 10) : string {
        $productService = ShopifyProductService::getInstance();

        $products = new Listing();
        $products->addConditionParam("export_to_shopify = ?", "1");
        $products->addConditionParam("shopify_sync = ?", "0");
        $products->setLimit("$count");
        return $this->exportProducts($productService, $products);
    }
}
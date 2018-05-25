<?php

namespace SintraPimcoreBundle\Controller\Sync;

use Pimcore\Tool\RestClient\Exception;
use SintraPimcoreBundle\Services\Magento2\Magento2ProductService;
use Pimcore\Model\DataObject\Product\Listing;

class Mage2SyncController extends BaseSyncController {
    protected $ecommerce = 'mage2';

    /**
     * @param int $count
     * @return string
     * @throws \Exception
     */
    public function syncProducts (int $count = 10) : string {
        $productUtils = Magento2ProductService::getInstance();

        $products = new Listing();
        $products->addConditionParam("export_to_magento = ?", "1");
        $products->addConditionParam("magento_syncronized = ?", "0");
        $products->setLimit("$count");
        return $this->exportProducts($productUtils, $products);
    }
}
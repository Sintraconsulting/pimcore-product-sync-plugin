<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Product;

class ShopifyProductImageModel {
    /** @var array */
    protected $images;
    /** @var array */
    protected $variants;
    /** @var array */
    protected $imagesJson;

    function __construct (array $variants, array $serverInfos) {
        $this->variants = $variants;
        $this->imagesJson = $this->parseImagesJsonFromServerInfo($serverInfos);
        $this->images = $this->parseImagesFromVariants();
    }

    protected function parseImagesJsonFromServerInfo (array $serverInfos) {
        $response = [];
        /** @var ServerObjectInfo $serverInfo */
        foreach ($serverInfos as $varId => $serverInfo) {
            $response += [ $varId => json_decode($serverInfo->getImages_json(), true)];
        }
        return $response;
    }

    protected function parseImagesFromVariants () {
        $imagesArray = [];
        /**
         * @var int $id
         * @var Product $variant
         */
        foreach ($this->variants as $id => $variant) {
            $imagesArray = array_merge_recursive($imagesArray, $this->getVariantImagesFormatted($variant));
        }
        return $imagesArray;
    }

    protected function getVariantImagesFormatted (Product $variant) {
        $imagesArray = [];
        $images = $variant->getImages();
        foreach ($images as $image) {
            $imagesJson = $this->imagesJson[$variant->getId()];
        }
    }
}
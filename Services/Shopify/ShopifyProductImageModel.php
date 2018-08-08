<?php

namespace SintraPimcoreBundle\Services\Shopify;


use Pimcore\Logger;
use Pimcore\Model\DataObject\Fieldcollection\Data\ImageInfo;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Product;

class ShopifyProductImageModel {
    /** @var array */
    protected $images;
    /** @var Product */
    protected $variant;
    /** @var array */
    protected $imagesJson;
    /** @var ServerObjectInfo */
    protected $serverInfo;

    function __construct (Product $variant, ServerObjectInfo $serverInfo) {
        $this->variant = $variant;
        $this->serverInfo = $serverInfo;
        $this->imagesJson = $this->getParsedImagesJsonFromServerInfo();
        $this->images = $this->parseImagesFromVariant();
    }

    public function getImagesArray() {
        return $this->images;
    }

    protected function getParsedImagesJsonFromServerInfo () {
        return json_decode($this->serverInfo->getImages_json(), true);
    }

    protected function parseImagesFromVariant () {
        return $this->getVariantImagesFormatted();
    }

    protected function getVariantImagesFormatted () : array {
        $imagesArray = [];
        /** @var ImageInfo $images */
        $images = $this->variant->getImages();
        $imagesJson = $this->imagesJson[$this->variant->getId()] ?? [];
        if (isset($images)) {
            /** @var ImageInfo $image */
            foreach ($images as $key => $image) {
                $shouldUploadImg = $this->shouldUploadImage($image, $imagesJson);
                $imagesArray[] = $this->buildImageArray($image, $imagesJson, $shouldUploadImg, $key === 0 ? $this->variant->getId() : null);
            }
        }
        return $imagesArray;
    }

    protected function buildImageArray (ImageInfo $imageInfo, array $imagesJson, bool $shouldUpload = false, $firstVarId = null) : array {
        $imgArray = [];
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        if ($shouldUpload) {
            $imgArray += ['src' => $imageInfo->getImageurl()->getUrl()];
        } else {
            $imgArray += ['id' => $imgCache];
        }
        if (isset($imgCache) && count($imgCache['variant_ids'])) {
            $imgArray += ['variant_ids' => $imgCache['variant_ids']];
        } elseif (isset($firstVarId)) {
            $imgArray += ['variant_ids' => [$this->serverInfo->getVariant_id()]];
        }
        return $imgArray;
    }

    protected function shouldUploadImage (ImageInfo $imageInfo, array $imagesJson) : bool {
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        if (isset($imgCache) && is_array($imgCache) && count($imgCache)) {
            return $imgCache['hash'] === $imageInfo->getHash();
        }
        return isset($imgCache) ? false : true;
    }

    protected function getImageInfoFromCache (ImageInfo $imageInfo, array $imagesCache) {
        foreach ($imagesCache as $imgCache) {
            if ($imageInfo->getFilename() === $imgCache['fileName']) {
                return $imgCache;
            }
        }
        return null;
    }
}
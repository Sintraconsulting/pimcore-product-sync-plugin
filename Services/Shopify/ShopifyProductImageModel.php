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
    /** @var int */
    protected $size;
    /**
     * Number of images that require upload in this iteration
     * Should not exceed the $maxUpload from constructor
     * @var int
     */
    protected $uploadCount;

    function __construct (Product $variant, ServerObjectInfo $serverInfo, $maxUpload = 10, int $countSize = 0) {
        $this->variant = $variant;
        $this->serverInfo = $serverInfo;
        $this->serverInfo->setSync(false);
        $this->serverInfo->setImages_sync(false);
        $this->variant->update(true);
        $this->imagesJson = $this->getParsedImagesJsonFromServerInfo();
        $this->size = $countSize;
        $this->uploadCount = 0;
        $this->images = $this->parseImagesFromVariant($maxUpload);
    }

    public function getImagesArray() {
        return $this->images;
    }

    public function getUploadCount () {
        return $this->uploadCount;
    }

    protected function getParsedImagesJsonFromServerInfo () {
        $jsonArray = json_decode($this->serverInfo->getImages_json(), true);
        return $jsonArray;
    }

    protected function parseImagesFromVariant ($maxUpload) {
        return $this->getVariantImagesFormatted($maxUpload);
    }

    protected function getVariantImagesFormatted ($maxUpload) : array {
        $imagesArray = [];
        /** @var ImageInfo $images */
        $images = $this->variant->getImages();
        $imagesJson = $this->imagesJson ?? [];
        Logger::log('IMG JSON!!');
        Logger::log(json_encode($imagesJson));
        if (isset($images)) {
            /** @var ImageInfo $image */
            foreach ($images as $key => $image) {
                $shouldUploadImg = $this->shouldUploadImage($image, $imagesJson);
                Logger::log('IMG JSON UPDATE!!');
                Logger::log($shouldUploadImg);
                if ( ($shouldUploadImg && $this->uploadCount < $maxUpload) || (!$shouldUploadImg)) {
                    $imagesArray[] = $this->buildImageArray($image, $imagesJson, $shouldUploadImg, $key === 0 ? $this->variant->getId() : null);
                    if ($shouldUploadImg) {
                        $this->uploadCount += 1;
                    }
                }
            }
        }
        return $imagesArray;
    }

    protected function buildImageArray (ImageInfo $imageInfo, array $imagesJson, bool $shouldUpload = false, $firstVarId = null) : array {
        $imgArray = [];
        $imagesShouldSync = $this->serverInfo->getImages_sync();
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        if (!isset($imagesShouldSync) || !$imagesShouldSync) {
            if ($shouldUpload) {
                $imgArray += ['src' => $imageInfo->getImageurl()->getUrl()];
            } else {
                $imageInfoIndex = $imageInfo->getIndex() + $this->size + 1;
                Logger::log('IMG CACHER INDEX');
                Logger::log($this->size);
                Logger::log($imageInfoIndex);
                $imgArray += ['id' => $imgCache['id']];
                if ($imageInfoIndex != $imgCache['position']) {
                    $imgArray += ['position' => $imageInfoIndex];
                }
            }

            if (isset($firstVarId) || $imageInfo->getIndex() === 0) {
                if (isset($imgCache) && count($imgCache['variant_ids'])) {
                    $imgArray += ['variant_ids' => $imgCache['variant_ids']];
                } else {
                    $imgArray += ['variant_ids' => [$this->serverInfo->getVariant_id()]];
                }
            } else {
                $imgArray += ['variant_ids' => []];
            }
        } else {
            $imgArray += ['id' => $imgCache['id']];
            $imageInfoIndex = $imageInfo->getIndex() + $this->size + 1;
            Logger::log('IMG CACHER INDEX');
            Logger::log(json_encode($imgCache));
            if ($imageInfoIndex != $imgCache['position']) {
                $imgArray += ['position' => $imageInfoIndex];
            }
        }
        $imageHash = $this->getImageHash($imageInfo);
        $imgArray += ['hash' => $imageHash];
        $imgArray += ['name' => $imageInfo->getFilename()];
        $imgArray += ['pimcore_index' => $imageInfo->getIndex()];
        return $imgArray;
    }

    protected function getImageHash (ImageInfo $imageInfo) {
        return $imageInfo->getHash();
    }

    protected function shouldUploadImage (ImageInfo $imageInfo, array $imagesJson) : bool {
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        if (isset($imgCache) && is_array($imgCache) && count($imgCache)) {
            return $imgCache['hash'] !== $imageInfo->getHash();
        }
        return isset($imgCache) ? false : true;
    }

    protected function getImageInfoFromCache (ImageInfo $imageInfo, array $imagesCache) {
        foreach ($imagesCache as $imgCache) {
            if ($imageInfo->getFilename() === $imgCache['name']) {
                return $imgCache;
            }
        }
        return null;
    }
}

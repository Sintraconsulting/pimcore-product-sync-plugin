<?php

namespace SintraPimcoreBundle\Services\Shopify;

use Pimcore\Model\DataObject\Fieldcollection\Data\ImageInfo;
use Pimcore\Model\DataObject\Fieldcollection\Data\ExternalImageInfo;
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

    function __construct(Product $variant, ServerObjectInfo $serverInfo, $maxUpload = 10, int $countSize = 0) {
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

    public function getUploadCount() {
        return $this->uploadCount;
    }

    protected function getParsedImagesJsonFromServerInfo() {
        $jsonArray = json_decode($this->serverInfo->getImages_json(), true);
        return $jsonArray;
    }

    protected function parseImagesFromVariant($maxUpload) {
        return $this->getVariantImagesFormatted($maxUpload);
    }

    protected function getVariantImagesFormatted($maxUpload): array {
        $imagesArray = [];

        $images = $this->variant->getImages();
        $imagesJson = $this->imagesJson ?? [];

        if (isset($images)) {
            foreach ($images as $key => $image) {
                $shouldUploadImg = $this->shouldUploadImage($image, $imagesJson);

                if (($shouldUploadImg && $this->uploadCount < $maxUpload) || (!$shouldUploadImg)) {
                    $imagesArray[] = $this->buildImageArray($image, $imagesJson, $shouldUploadImg, $key === 0 ? $this->variant->getId() : null);
                    if ($shouldUploadImg) {
                        $this->uploadCount += 1;
                    }
                }
            }
        }
        return $imagesArray;
    }

    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesJson
     * @param bool $shouldUpload
     * @param type $firstVarId
     * @return array
     */
    protected function buildImageArray($imageInfo, array $imagesJson, bool $shouldUpload = false, $firstVarId = null): array {
        $imgArray = [];
        $imagesShouldSync = $this->serverInfo->getImages_sync();
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);

        if (!isset($imagesShouldSync) || !$imagesShouldSync) {
            if ($shouldUpload) {
                $imgArray += ['src' => $this->getImageUrl($imageInfo)];
            } else {
                $imageInfoIndex = $imageInfo->getIndex() + $this->size + 1;

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

            if ($imageInfoIndex != $imgCache['position']) {
                $imgArray += ['position' => $imageInfoIndex];
            }
        }
        # Added the hashing function for extension
        $imageHash = $this->getImageHash($imageInfo);
        $imgArray += ['hash' => $imageHash];
        $imgArray += ['name' => $this->getImageFilename($imageInfo)];
        $imgArray += ['pimcore_index' => $imageInfo->getIndex()];
        return $imgArray;
    }

    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesJson
     * @return bool
     */
    protected function shouldUploadImage($imageInfo, array $imagesJson): bool {
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        if (isset($imgCache) && is_array($imgCache) && count($imgCache)) {
            return $imgCache['hash'] !== $this->getImageHash($imageInfo);
        }
        return isset($imgCache) ? false : true;
    }

    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesCache
     * @return type
     */
    protected function getImageInfoFromCache($imageInfo, array $imagesCache) {

        foreach ($imagesCache as $imgCache) {
            if ($this->getImageFilename($imageInfo) === $imgCache['name']) {
                return $imgCache;
            }
        }

        return null;
    }

    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @return type
     */
    protected function getImageHash($imageInfo) {
        if ($imageInfo instanceof ExternalImageInfo) {
            return $imageInfo->getHash();
        } else if ($imageInfo instanceof ImageInfo) {
            $image = $imageInfo->getImage();
            return $image->getFileSize();
        }
    }

    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @return type
     */
    protected function getImageFilename($imageInfo) {

        if ($imageInfo instanceof ExternalImageInfo) {
            return $imageInfo->getFilename();
        } else if ($imageInfo instanceof ImageInfo) {
            $image = $imageInfo->getImage();
            return $image->getFilename();
        }
    }
    
    /**
     * 
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @return type
     */
    protected function getImageUrl($imageInfo) {

        if ($imageInfo instanceof ExternalImageInfo) {
            return $imageInfo->getImageurl()->getUrl();
        } else if ($imageInfo instanceof ImageInfo) {
            $image = $imageInfo->getImage();
            return \Pimcore\Tool::getHostUrl() . $image->getFullPath();
        }
    }

}

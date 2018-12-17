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

    /**
     * Creates a default status for every ImageModel
     *
     * ShopifyProductImageModel constructor.
     * @param Product $variant
     * @param ServerObjectInfo $serverInfo
     * @param int $maxUpload
     * @param int $countSize
     * @throws \Exception
     */
    function __construct(Product $variant, ServerObjectInfo $serverInfo, $maxUpload = 10, int $countSize = 0) {
        $this->variant = $variant;
        $this->serverInfo = $serverInfo;
        $this->serverInfo->setSync(false);
        $this->serverInfo->setImages_sync(false);
        # Force images not sync since there are images to be uploaded for this variant
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

    /**
     * Builds the images array for every variant
     * In this method is decided if the image should be (re)uploaded or not
     *
     * @param $maxUpload
     * @return array
     */
    protected function getVariantImagesFormatted($maxUpload): array {
        $imagesArray = [];

        $images = $this->variant->getImages();
        $imagesJson = $this->imagesJson ?? [];

        if (isset($images)) {
            # Goes through all the variant's images
            foreach ($images as $key => $image) {
                $shouldUploadImg = $this->shouldUploadImage($image, $imagesJson);

                # If the limit is not reached or it doesn't require reupload,
                # continue to increase image json
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
     * Builds the image array to be included in the request
     * Includes references like pimcore_index for data manipulation in future shopify requests
     *
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesJson
     * @param bool $shouldUpload
     * @param string $firstVarId
     * @return array
     */
    protected function buildImageArray($imageInfo, array $imagesJson, bool $shouldUpload = false, $firstVarId = null): array {
        $imgArray = [];
        $imagesShouldSync = $this->serverInfo->getImages_sync();
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);

        # Check if the variant's images have been changed
        if (!isset($imagesShouldSync) || !$imagesShouldSync) {
            # If this image requires (re)upload
            if ($shouldUpload) {
                # By including 'src' key, we tell shopify it requires (re)upload
                $imgArray += ['src' => $this->getImageUrl($imageInfo)];
            } else {
                $imageInfoIndex = $imageInfo->getIndex() + $this->size + 1;

                $imgArray += ['id' => $imgCache['id']];
                # If position has been changed
                if ($imageInfoIndex != $imgCache['position']) {
                    $imgArray += ['position' => $imageInfoIndex];
                }
            }

            # If it's the first image of the variant
            if (isset($firstVarId) || $imageInfo->getIndex() === 0) {
                if (isset($imgCache) && count($imgCache['variant_ids'])) {
                    $imgArray += ['variant_ids' => $imgCache['variant_ids']];
                } else {
                    $imgArray += ['variant_ids' => [$this->serverInfo->getVariant_id()]];
                }
            } else {
                # else force clear other images from changing the pointer
                $imgArray += ['variant_ids' => []];
            }
        } else {
            # Add id for shopify to keep the old image
            # If the image is not present, it's deleted by shopify api.
            $imgArray += ['id' => $imgCache['id']];
            $imageInfoIndex = $imageInfo->getIndex() + $this->size + 1;

            # If position has been changed in pimcore, reposition in shopify as well
            if ($imageInfoIndex != $imgCache['position']) {
                $imgArray += ['position' => $imageInfoIndex];
            }
        }
        # Added the hashing function for extension
        $imageHash = $this->getImageHash($imageInfo);
        $imgArray += ['hash' => $imageHash];
        $imgArray += ['name' => $this->getImageFilename($imageInfo)];
        # Remember pimcore_index for future improvements
        $imgArray += ['pimcore_index' => $imageInfo->getIndex()];
        return $imgArray;
    }

    /**
     * Compares images hash or if cache exists and decides if (re)upload is required
     *
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesJson
     * @return bool
     */
    protected function shouldUploadImage($imageInfo, array $imagesJson): bool {
        $imgCache = $this->getImageInfoFromCache($imageInfo, $imagesJson);
        # If there are images in the cached field
        if (isset($imgCache) && is_array($imgCache) && count($imgCache)) {
            # Invalid hash results in (re)upload
            return $imgCache['hash'] !== $this->getImageHash($imageInfo);
        }
        return isset($imgCache) ? false : true;
    }

    /**
     * Loads the cache of the specific image by name
     *
     * @param ImageInfo|ExternalImageInfo $imageInfo
     * @param array $imagesCache
     * @return mixed
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

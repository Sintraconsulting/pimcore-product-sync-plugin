<?php

namespace SintraPimcoreBundle\Services\Shopify;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;
use Pimcore\Model\DataObject\Product;

/**
 * Provide methods and utils for objects synchronization for Shopify servers
 * common for all object classes.
 * Must be extended for specific needs.
 *
 * @author Sintra Consulting
 */
abstract class BaseShopifyService extends BaseEcommerceService {

    /**
     * Specific mapping for Shopify Product export
     * It builds the API array for communcation with shopify product endpoint
     * Assumes that the $shopifyApi has already prebuilt the variants subarray
     *
     * @param $shopifyApi
     * @param FieldMapping $fieldMap
     * @param array $fieldsDepth
     * @param $language
     * @param $dataSource
     * @param TargetServer $server
     * @return array
     * @throws \Exception
     */
    protected function mapServerMultipleField ($shopifyApi, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {
        # End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Product\Listing $dataSource */
            # If we have a Listing as $dataSource, get the first object
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
                /** @var Concrete $dataSource */
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            /** @var string $apiField - name of the last field after . split from mapping*/
            $apiField = $fieldsDepth[0];

            if($fieldValue instanceof \Pimcore\Model\DataObject\Data\QuantityValue && $apiField == 'weight'){
                # Generate also the weight_unit for the specific quantity value
                return $this->mapServerField($shopifyApi, $fieldValue->getValue(), $apiField) + $this->mapServerField([], $fieldValue->getUnit()->getAbbreviation(), 'weight_unit');
            } elseif($apiField == 'price'){
                # IF field value is null or 0, set default as 9999 to avoid free purchases
                if($fieldValue === null || $fieldValue->getValue() === null || (int)$fieldValue === 0 || (int)$fieldValue->getValue() === 0) {
                    $fieldValue = 9999.99;
                }
                return $this->mapServerField($shopifyApi, $fieldValue, $apiField);
            } elseif ($apiField === 'tags') {
                if (isset($shopifyApi['tags'])) {
                    $otherTags = explode(', ', $shopifyApi['tags']);
                    $otherTags[] = is_array($fieldValue) ? implode(', ',$fieldValue) : $fieldValue;
                    # Transform array to string to comply with shopify's type requirements
                    $fieldValue = implode(", ", $otherTags);
                }
            }
            return $this->mapServerField($shopifyApi, $fieldValue, $apiField);
        }
        # Removes first element as assign it as parent element
        # Required for depth recursion
        $parentDepth = array_shift($fieldsDepth);

        # Recursion inside variants
        if ($parentDepth == 'variants' && $dataSource) {
            $i = 0;
            foreach ($dataSource as $dataObject) {
                $serverInfo = GeneralUtils::getServerObjectInfo($dataObject, $server);

                /**
                 * If a variant needs to be synced, do the recursion.
                 * If not, only take tags from the variant to avoid
                 * the deletion of tags from the product
                 */
                if (!$serverInfo->getSync() || $fieldsDepth[0] == "tags") {
                    $shopifyApi[$parentDepth][$i] = $this->mapServerMultipleField($shopifyApi[$parentDepth][$i],
                            $fieldMap, $fieldsDepth, $language, $dataObject);
                }
                $i++;
            }
            /** @var array $shopifyApi */
            return $shopifyApi;
        }

        /**
         * End of recursion with metafields
         * @see https://help.shopify.com/en/api/reference/metafield
         * TODO: could be exported as a self sustainable function, but for now it's not necessary
         */
        if ($parentDepth == 'metafields') {
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];
            $fieldType = is_integer($fieldValue) ? 'integer' : 'string';
            if (isset($fieldValue) && !empty($fieldValue)) {
                $customValue = [
                        'key' => $apiField,
                        'value' => $fieldType === 'string' ? (string)$fieldValue : $fieldValue,
                        'value_type' => $fieldType,
                    // Namespace is intentional like this so we know it was generated by SintraPimcoreBundle
                        'namespace' => 'SintraPimcore',
                ];
                $shopifyApi[$parentDepth][] = $customValue;
            }
            /** @var array $shopifyApi */
            return $shopifyApi;
        }

        /**
         * Recursion level > 1
         * For now, on shopify there is no nested field mapping except metafields & variants
         * It should never reach this point with shopify.
         * TODO: image implementation should be developed in the future here for field mapping
         */
        return $this->mapServerMultipleField($shopifyApi[$parentDepth], $fieldMap, $fieldsDepth, $language, $dataSource, $server);
    }

}

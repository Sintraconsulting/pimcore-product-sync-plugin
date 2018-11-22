<?php

namespace SintraPimcoreBundle\Services\Shopify;

use SintraPimcoreBundle\Services\BaseEcommerceService;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Utils\GeneralUtils;
use Pimcore\Logger;

abstract class BaseShopifyService extends BaseEcommerceService {

    /**
     * Specific mapping for Shopify Product export
     * It builds the API array for communcation with shopify product endpoint
     * @param $shopifyApi
     * @param $fieldMap
     * @param $fieldsDepth
     * @param $language
     * @param $dataSource
     * @param TargetServer $server
     * @return array
     * @throws \Exception
     */
    protected function mapServerMultipleField ($shopifyApi, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null) {
        // End of recursion
        if(count($fieldsDepth) == 1) {
            /** @var Product\Listing $dataSource */
            if ( method_exists($dataSource, 'current') ) {
                $dataSource = $dataSource->getObjects()[0];
            }
            $fieldValue = $this->getObjectField($fieldMap, $language, $dataSource);
            $apiField = $fieldsDepth[0];

            if($fieldValue instanceof \Pimcore\Model\DataObject\Data\QuantityValue && $apiField == 'weight'){
                return $this->mapServerField($shopifyApi, $fieldValue->getValue(), $apiField) + $this->mapServerField([], $fieldValue->getUnit()->getAbbreviation(), 'weight_unit');
            } elseif($apiField == 'price'){

                if($fieldValue === null || $fieldValue->getValue() === null || (int)$fieldValue === 0 || (int)$fieldValue->getValue() === 0) {
                    $fieldValue = 9999.99;
                }
                return $this->mapServerField($shopifyApi, $fieldValue, $apiField);
            } elseif ($apiField === 'tags') {
                if (isset($shopifyApi['tags'])) {
                    $otherTags = explode(', ', $shopifyApi['tags']);
                    $otherTags[] = is_array($fieldValue) ? implode(', ',$fieldValue) : $fieldValue;
                    $fieldValue = implode(", ", $otherTags);
                }
            }
            return $this->mapServerField($shopifyApi, $fieldValue, $apiField);
        }
        $parentDepth = array_shift($fieldsDepth);

        //Recursion inside variants
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

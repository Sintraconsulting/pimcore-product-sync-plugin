<?php

namespace SintraPimcoreBundle\Services\Shopify;

use SintraPimcoreBundle\Services\BaseEcommerceService;

abstract class BaseShopifyService extends BaseEcommerceService {
    protected function insertSingleValue (&$ecommObject, $fieldName, $fieldvalue, $isBrick = false) {
        if ($isBrick) {
            return;
        }

        if (array_key_exists($fieldName, $ecommObject)) {
            $ecommObject[$fieldName] = $fieldvalue;
        } else {
            $shopifyField = '';
            switch ($fieldName) {
                case "name":
                    $shopifyField = 'title';
                    break;
                case "status":
                    $fieldvalue = (boolean)$fieldvalue;
                    $shopifyField = 'published';
                    break;
                case "description":
                    $shopifyField = 'body_html';
                    break;
                case "sku":
                case "price":
                case "weight":
                    $shopifyField = 'variants';
                    break;
                default :
                    $shopifyField = 'metafields';
            }
            $this->parseField($ecommObject, $shopifyField, $fieldName, $fieldvalue);
        }
    }

    protected function parseField (&$ecommObject, $shopifyField, $origField, $origValue) {
        if ($shopifyField == 'variants') {
            $ecommObject['variants'][0][$origField] = $origValue;
        } else if ($shopifyField == 'metafields') {
            if (!in_array($origField, $this->productExportHidden)) {
                $ecommObject['metafields'][] = [
                        "key" => $origField,
                        "value" => $origValue,
                        "value_type" => "string",
                        "namespace" => "global"
                ];
            }
        } else {
            $ecommObject[$shopifyField] = $origValue;
        }
    }
}
<?php

namespace SintraPimcoreBundle\Services\Shopify;

use SintraPimcoreBundle\Services\BaseEcommerceService;

abstract class BaseShopifyService extends BaseEcommerceService {
    protected function insertSingleValue (&$ecommObject, $fieldName, $fieldvalue, $isBrick = false) {
        
        if (array_key_exists($fieldName, $ecommObject)) {
            $ecommObject[$fieldName] = $fieldvalue;
        } else if ($ecommObject["key"] == $fieldName) {
            $ecommObject["value"] = $fieldvalue;
        } else {
            //recursion
            foreach ($ecommObject as $key => $field) {
                if (is_array($field)) {
                    $this->insertSingleValue($ecommObject[$key], $fieldName, $fieldvalue);
                }
            }
        }
        
    }
}
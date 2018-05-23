<?php

namespace SintraPimcoreBundle\Services;

abstract class BaseShopifyService extends BaseEcommerceService {
    protected function insertSingleValue (&$ecommObject, $fieldName, $fieldvalue, $isBrick = false) {
        if ($isBrick) {
            $ecommObject["custom_attributes"][] = array(
                    "attribute_code" => $fieldName,
                    "value" => $fieldvalue
            );
            return;
        }

        if (array_key_exists($fieldName, $ecommObject)) {
            $ecommObject[$fieldName] = $fieldvalue;
        } else if ($ecommObject["attribute_code"] == $fieldName) {
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
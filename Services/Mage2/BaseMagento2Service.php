<?php
namespace SintraPimcoreBundle\Services\Mage2;

use SintraPimcoreBundle\Services\BaseEcommerceService;
/**
 * Magento 2 Shop level logic
 * Needs to implement BaseEcommerceService abstract functions
 * Class Magento2Service
 */
abstract class BaseMagento2Service extends BaseEcommerceService {
    /**
     * Search for field name in the API call object skeleton
     * and fill that with the field value.
     * 
     * @param type $ecommObject the object to fill for the API call
     * @param type $fieldName the field name
     * @param type $fieldvalue the field value
     */
    protected function insertSingleValue (&$ecommObject, $fieldName, $fieldvalue) {

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
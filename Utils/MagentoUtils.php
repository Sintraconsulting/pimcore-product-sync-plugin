<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Magento2PimcoreBundle\Utils;

use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Objectbrick;

/**
 * Utils for mapping Pimcore Objects to Magento2 Objects
 *
 * @author Marco Guiducci
 */
class MagentoUtils {

    public function mapField(&$magento2Object, $fieldName, $fieldType, $fieldValue, $classId) {
        switch ($fieldType) {
            case "quantityValue":
                $this->insertSingleValue($magento2Object, $fieldName, $fieldValue->value);
                break;

            case "numeric":
                $this->insertSingleValue($magento2Object, $fieldName, $fieldValue);
                break;

            case "localizedfields":
                $localizedFields = $fieldValue->getItems();
                if ($localizedFields != null && count($localizedFields) > 0) {
                    $this->insertLocalizedFields($magento2Object, $localizedFields);
                }
                break;

            case "objectbricks":
                $objectBricks = $fieldValue ? $fieldValue->getItems() : null;
                if ($objectBricks != null && count($objectBricks) > 0) {
                    $this->insertObjectBricks($magento2Object, $objectBricks, $classId);
                }
                break;

            case "objects":
                break;

            default:
                $this->insertSingleValue($magento2Object, $fieldName, $fieldValue);
                break;
        }
    }

    private function insertSingleValue(&$magento2Object, $fieldName, $fieldvalue, $isBrick = false) {
        if($isBrick){
            $magento2Object["custom_attributes"][] = array(
                "attribute_code" => $fieldName,
                "value" => $fieldvalue
            );
            return;
        }
        
        if(array_key_exists($fieldName, $magento2Object)){
            $magento2Object[$fieldName] = $fieldvalue;
        }else if($magento2Object["attribute_code"] == $fieldName){
            $magento2Object["value"] = $fieldvalue;
        }else{
            //recursion
            foreach ($magento2Object as $key => $field) {
                if(is_array($field)){
                    $this->insertSingleValue($magento2Object[$key], $fieldName, $fieldvalue);
                }
            }
        }
    }

    private function insertLocalizedFields(&$magento2Object, $localizedFields) {
        $config = \Pimcore\Config::getSystemConfig();
        $lang = $config->general->language;
        foreach ($localizedFields[$lang] as $fieldName => $fieldvalue) {
            $this->insertSingleValue($magento2Object, $fieldName, $fieldvalue);
        }
    }

    private function insertObjectBricks(&$magento2Object, $objectBricks, $classId) {
        foreach ($objectBricks as $objectBrick) {
            $type = $objectBrick->type;

            $db = Db::get();
            $brickfields = $db->fetchRow("SELECT * FROM object_brick_store_" . $type . "_" . $classId);

            foreach ($brickfields as $fieldName => $fieldvalue) {
                if (!in_array($fieldName, array("o_id", "fieldname"))) {
                    $this->insertSingleValue($magento2Object, $fieldName, $objectBrick->getValueForFieldName($fieldName), true);
                }
            }
        }
    }

}

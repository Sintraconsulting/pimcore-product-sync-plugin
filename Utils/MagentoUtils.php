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
                $objectBricks = $fieldValue->getItems();
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

    public function insertSingleValue(&$magento2Object, $fieldName, $fieldvalue) {
        if (strpos($fieldName, "custom_") === 0) {
            $field = str_replace("custom_", "", $fieldName, $i = 1);
            $magento2Object["custom_attributes"][] = array(
                "attribute_code" => $field,
                "value" => $fieldvalue
            );
        } else {
            $magento2Object[$fieldName] = $fieldvalue;
        }
    }

    public function insertLocalizedFields(&$magento2Object, $localizedFields) {
        foreach ($localizedFields["en"] as $fieldName => $fieldvalue) {
            $this->insertSingleValue($magento2Object, "custom_" . $fieldName, $fieldvalue);
        }
    }

    public function insertObjectBricks(&$magento2Object, $objectBricks, $classId) {
        foreach ($objectBricks as $objectBrick) {
            $type = $objectBrick->type;

            $db = Db::get();
            $brickfields = $db->fetchRow("SELECT * FROM object_brick_query_" . $type . "_" . $classId);

            foreach ($brickfields as $fieldName => $fieldvalue) {
                if (!in_array($fieldName, array("o_id", "fieldname"))) {
                    $this->insertSingleValue($magento2Object, "custom_" . $fieldName, $fieldvalue);
                }
            }
        }
    }

}

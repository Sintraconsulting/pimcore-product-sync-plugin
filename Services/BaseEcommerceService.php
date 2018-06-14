<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Logger;

/**
 * Extending classes have to define their own functionality for custom attributes.
 * This is generic Shop level logic
 * Class EcommerceService
 */
abstract class BaseEcommerceService extends SingletonService{
    protected $productExportHidden = [
            'shopify_id', 'export_to_magento', 'export_to_shopify', 'magento_sync',
            'magento_sync_at', 'shopify_sync', 'shopify_sync_at'
    ];

    abstract protected function insertSingleValue(&$ecommObject, $fieldName, $fieldvalue, $isBrick = false);

    public function mapField(&$ecommObject, $fieldName, $fieldType, $fieldValue, $classId) {
        switch ($fieldType) {
            case "quantityValue":
                $this->insertSingleValue($ecommObject, $fieldName, $fieldValue->value);
                break;

            case "numeric":
                $numericValue = $fieldValue ? $fieldValue : 0;
                $this->insertSingleValue($ecommObject, $fieldName, $numericValue);
                break;

            case "localizedfields":
                $localizedFields = $fieldValue->getItems();
                if ($localizedFields != null && count($localizedFields) > 0) {
                    $this->insertLocalizedFields($ecommObject, $localizedFields);
                }
                break;

            case "objectbricks":
                $objectBricks = $fieldValue ? $fieldValue->getItems() : null;
                if ($objectBricks != null && count($objectBricks) > 0) {
                    $this->insertObjectBricks($ecommObject, $objectBricks, $classId);
                }
                break;

            case "select":
                $selectValue = $fieldValue ? $fieldValue : null;
                $this->insertSingleValue($ecommObject, $fieldName, $selectValue);
                
            case "objects":
                break;

            default:
                $this->insertSingleValue($ecommObject, $fieldName, $fieldValue);
                break;
        }
    }

    protected function insertLocalizedFields(&$ecommObject, $localizedFields) {
        try{
            $config = \Pimcore\Config::getSystemConfig();
        } catch (\Exception $e) {
            Logger::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
        $languages = explode(",",$config->general->validLanguages);

        foreach ($localizedFields[$languages[0]] as $fieldName => $fieldvalue) {
            $this->insertSingleValue($ecommObject, $fieldName, $fieldvalue);
        }
    }

    protected function insertObjectBricks(&$ecommObject, $objectBricks, $classId) {
        foreach ($objectBricks as $objectBrick) {
            $type = $objectBrick->type;

            $db = Db::get();
            $brickfields = $db->fetchRow("SELECT * FROM object_brick_store_" . $type . "_" . $classId);

            foreach ($brickfields as $fieldName => $fieldvalue) {
                if (!in_array($fieldName, array("o_id", "fieldname"))) {
                    $this->insertSingleValue($ecommObject, $fieldName, $objectBrick->getValueForFieldName($fieldName), true);
                }
            }
        }
    }
}
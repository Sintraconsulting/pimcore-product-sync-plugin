<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Data\QuantityValue;

/**
 * Event Listener Utils
 *
 * @author Sintra Consulting
 */
class EventListenerUtils {

    /**
     * Insert missing field collections to the product's exportServers.
     * 
     * Take server keys (e.g Magento, Shopify, etc.) from the currently present field collections
     * and list the missing servers to map.
     * For each of them, search for the specific field collection definition
     * and add a new instance to the product's exportServers.
     * 
     * @param Fieldcollection $exportServers
     */
    public static function insertMissingFieldCollections(&$exportServers) {
        $serverKeys = array();
        foreach ($exportServers as $exportServer) {
            $serverKeys[] = $exportServer->getServer()->getKey();
        }

        $targetServers = new TargetServer\Listing();
        $targetServers->setCondition("o_key NOT IN ('" . implode("','", $serverKeys) . "')");

        $next = $targetServers->count() > 0;
        while ($next) {
            $targetServer = $targetServers->current();

            $fieldCollectionClassName = "\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\ServerObjectInfo";
            $fieldCollectionClass = new \ReflectionClass($fieldCollectionClassName);
            $fieldCollection = $fieldCollectionClass->newInstance();
            $fieldCollection->setExport(true);
            $fieldCollection->setName($targetServer->getKey());
            $fieldCollection->setServer($targetServer);

            $exportServers->add($fieldCollection);

            $next = $targetServers->next();
        }
    }

    /**
     * For a specific field collection implementation 
     * check if product's fields to export are changed.
     * 
     * If at least a field value has changed, 
     * product must be syncronized in the server.
     * 
     * @param $exportServer the specific field collection implementation for a server
     * @param Concrete $dataObject the new version of the object to update
     * @param Concrete $oldDataObject the previous version of the object
     * @return boolean
     */
    public static function checkServerUpdate($exportServer, $dataObject, $oldDataObject) {
        $export = false;

        $targetServer = $exportServer->getServer();
        $exportFields = TargetServerUtils::getClassExportFields($targetServer, $dataObject->getClassName());
        if ($exportFields != null) {
            $languages = $targetServer->getLanguages();

            foreach ($exportFields as $field) {
                if (!self::compareFieldValues($dataObject, $oldDataObject, $field, $languages)) {
                    $export = true;
                    break;
                }
            }
        }

        return $export;
    }

    /**
     * Check if all fields required for the server instance are not empty
     * If at least one required field is empty, mark the product as not complete
     * 
     * @param $exportServer the server instance
     * @param Concrete $dataObject the object to update
     * @return boolean
     */
    public static function checkObjectCompleted($exportServer, $dataObject) {
        $complete = true;

        $targetServer = $exportServer->getServer();
        $fieldsMap = TargetServerUtils::getClassFieldMap($targetServer, $dataObject->getClassName());

        if ($fieldsMap != null) {
            $languages = $targetServer->getLanguages();

            foreach ($fieldsMap as $fieldMap) {
                $field = $fieldMap->getObjectField();

                if ($fieldMap->getRequired() && self::checkFieldEmpty($dataObject, $field, $languages)) {
                    $complete = false;
                    break;
                }
            }
        }

        return $complete;
    }

    /**
     * Check if all fields required for the server instance are not empty
     * If at least one required field is empty, mark the product as not complete
     * 
     * @param $exportServer the server instance
     * @param Concrete $dataObject the object to update
     * @return boolean
     */
    public static function checkImagesChanged($exportServer, $dataObject) {

        $imagesJson = $exportServer->getImages_json();
        $savedImagesData = ($imagesJson != null && !empty($imagesJson)) ? json_decode($imagesJson, true) : array();

        $imagesInfo = GeneralUtils::getObjectImagesInfo($dataObject);

        if (sizeof($savedImagesData) != sizeof($imagesInfo)) {
            return true;
        }

        $changed = false;
        foreach ($imagesInfo as $position => $imageInfo) {
            if (method_exists($imageInfo, "getImage")) {
                $image = $imageInfo->getImage();

                $index = array_search($image->getId(), array_column($savedImagesData, "id"));

                if ($index === false) {
                    $changed = true;
                    break;
                }

                $savedImage = $savedImagesData[$index];
                if ($savedImage["position"] != $position || $savedImage["hash"] != $image->getFileSize()) {
                    $changed = true;
                    break;
                }
            }
        }

        return $changed;
    }
    
    /**
     * When a new variant is added or needs to be synchronize
     * also the parent object is marked as to synchronize
     * 
     * @param Concrete $parent the parent object
     */
    public static function updateParentSynchronizationInfo(Concrete $parent) {
        $exportServers = $parent->getExportServers() != null ? $parent->getExportServers() : new Fieldcollection();

        foreach ($exportServers as $exportServer) {
            if($exportServer instanceof ServerObjectInfo){
                $exportServer->setSync(false);
            }
        }

        $parent->setExportServers($exportServers);
        $parent->update(true);
    }
    

    /**
     * Compare new and previous value of a product field.
     * Reflection is used to abstract on all possible fields.
     * 
     * @param Concrete $dataObject the new version of the object to update
     * @param Concrete $oldDataObject the previous version of the object
     * @param String $field the specific field name
     * @param array $languages the valid languages for the server
     * @return boolean
     */
    private static function compareFieldValues($dataObject, $oldDataObject, $field, $languages) {
        $match = false;

        $classname = strtolower($dataObject->getClassName()) . "_";
        $fieldname = substr_replace($field, "", 0, strlen($classname));

        $methodName = "get" . ucfirst($fieldname);

        $method = new \ReflectionMethod("\\Pimcore\\Model\\DataObject\\" . ucfirst($dataObject->getClassName()), $methodName);
        $params = $method->getParameters();

        /**
         * check if the getter method for the field accept the "language" parameter.
         */
        $isLocalized = false;
        foreach ($params as $param) {
            if ($param->getName() == "language") {
                $isLocalized = true;
                break;
            }
        }

        $productReflection = new \ReflectionObject($dataObject);
        $oldProductReflection = new \ReflectionObject($oldDataObject);

        $newValueMethod = $productReflection->getMethod($methodName);
        $oldValueMethod = $oldProductReflection->getMethod($methodName);

        /**
         * If field is localized, check for changes in the server langages
         * Else just check for values equality
         */
        if ($isLocalized && !empty($languages)) {
            foreach ($languages as $lang) {
                $newValue = $newValueMethod->invoke($dataObject, $lang);
                $oldValue = $oldValueMethod->invoke($oldDataObject, $lang);

                if ($newValue == $oldValue) {
                    $match = true;
                    break;
                }
            }
        } else {
            $newValue = $newValueMethod->invoke($dataObject);
            $oldValue = $oldValueMethod->invoke($oldDataObject);

            if (self::compareValues($newValue, $oldValue)) {
                $match = true;
            }
        }

        return $match;
    }

    private static function compareValues($newValue, $oldValue) {
        if ($newValue instanceof QuantityValue && $oldValue instanceof QuantityValue) {
            return ($newValue->getValue() == $oldValue->getValue()) && ($newValue->getUnitId() == $oldValue->getUnitId());
        }

        if ($newValue instanceof Concrete && $oldValue instanceof Concrete) {
            return ($newValue->getId() == $oldValue->getId());
        }

        /** In case of multiple objects relation */
        if (is_array($newValue)) {

            if (sizeof($newValue) != sizeof($oldValue)) {
                return false;
            }

            $match = true;

            foreach ($newValue as $key => $value) {
                /**
                 * Comparison recursion on Pimcore natives array
                 * Tested and verified, but in future we may need to change
                 * this part to not compare values by array ID
                 */
                if (!self::compareValues($value, $oldValue[$key])) {
                    $match = false;
                    break;
                }
            }

            return $match;
        }

        return ($newValue == $oldValue);
    }

    /**
     * Check if a field is null or empty for an object
     * 
     * @param Concrete $dataObject the object to check
     * @param type $field the field to check
     * @param type $languages the languages to check for localized fields
     * @return boolean
     */
    private static function checkFieldEmpty($dataObject, $field, $languages) {
        $empty = false;

        $classname = strtolower($dataObject->getClassName()) . "_";
        $fieldname = substr_replace($field, "", 0, strlen($classname));

        $methodName = "get" . ucfirst($fieldname);

        $method = new \ReflectionMethod("\\Pimcore\\Model\\DataObject\\" . ucfirst($dataObject->getClassName()), $methodName);
        $params = $method->getParameters();

        /**
         * check if the getter method for the field accept the "language" parameter.
         */
        $isLocalized = false;
        foreach ($params as $param) {
            if ($param->getName() == "language") {
                $isLocalized = true;
                break;
            }
        }

        /**
         * If field is localized, check emptyness for each langage
         * Else just check for value emptyness
         */
        if ($isLocalized && !empty($languages)) {
            foreach ($languages as $lang) {
                if (self::isFieldEmpty($dataObject, $fieldname, $lang)) {
                    $empty = true;
                    break;
                }
            }
        } else {
            if (self::isFieldEmpty($dataObject, $fieldname)) {
                $empty = true;
            }
        }

        return $empty;
    }

    private static function isFieldEmpty(Concrete $dataObject, $fieldname, $lang = null) {
        $methodName = "get" . ucfirst($fieldname);

        $productReflection = new \ReflectionObject($dataObject);
        $fieldValueMethod = $productReflection->getMethod($methodName);

        if ($lang != null) {
            $fieldValue = $fieldValueMethod->invoke($dataObject, $lang);
        } else {
            $fieldValue = $fieldValueMethod->invoke($dataObject);
        }

        if ($fieldValue === null || $fieldValue === "") {
            $classDefinition = ClassDefinition::getByName($dataObject->getClassName());

            if ($classDefinition->getAllowInherit()) {
                $parentValue = $dataObject->getValueFromParent($fieldname);
                return ($parentValue === null || $parentValue === "");
            }

            return true;
        }

        return false;
    }

}

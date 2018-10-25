<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection;

use Pimcore\Model\DataObject\Data\QuantityValue;

/**
 * Event Listener Utils
 *
 * @author Marco Guiducci
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
    public static function insertMissingFieldCollections(&$exportServers){
        $serverKeys = array();
        foreach ($exportServers as $exportServer) {
            $serverKeys[] = $exportServer->getServer()->getKey();
        }

        $targetServers = new TargetServer\Listing();
        $targetServers->setCondition("o_key NOT IN ('".implode("','",$serverKeys)."')");
        
        $next = $targetServers->count() > 0;
        while($next){
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
    public static function checkServerUpdate($exportServer,$dataObject,$oldDataObject){
        $export = false;
            
        $targetServer = $exportServer->getServer();
        $exportFields = TargetServerUtils::getClassExportFields($targetServer, $dataObject->getClassName());
        if($exportFields != null){
            $languages = $targetServer->getLanguages();

            foreach ($exportFields as $field) {
                if(!self::compareFieldValues($dataObject, $oldDataObject, $field, $languages)){
                    $export = true;
                    break;
                }
            }
        }
        
        return $export;
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
    private static function compareFieldValues($dataObject, $oldDataObject, $field, $languages){
        $match = false;
        
        $classname = strtolower($dataObject->getClassName())."_";
        $fieldname = substr_replace($field, "", 0, strlen($classname));
        
        $methodName = "get". ucfirst($fieldname);
        
        $method = new \ReflectionMethod("\\Pimcore\\Model\\DataObject\\". ucfirst($dataObject->getClassName()),$methodName);
        $params = $method->getParameters();
        
        /**
         * check if the getter method for the field accept the "language" parameter.
         */
        $isLocalized = false;
        foreach ($params as $param) {
            if($param->getName() == "language"){
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
        if($isLocalized && !empty($languages)){
            foreach ($languages as $lang) {
                $newValue = $newValueMethod->invoke($dataObject, $lang);
                $oldValue = $oldValueMethod->invoke($oldDataObject, $lang);
                
                if($newValue == $oldValue){
                    $match = true;
                    break;
                }
            }
        }else{
            $newValue = $newValueMethod->invoke($dataObject);
            $oldValue = $oldValueMethod->invoke($oldDataObject);

            if(self::compareValues($newValue, $oldValue)){
                $match = true;
            }
        }
        
        return $match;
        
    }
    
    private static function compareValues($newValue, $oldValue){
        if($newValue instanceof QuantityValue && $oldValue instanceof QuantityValue){
            return ($newValue->getValue() == $oldValue->getValue()) && ($newValue->getUnitId() == $oldValue->getUnitId());
        }
        
        if($newValue instanceof Concrete && $oldValue instanceof Concrete){
            return ($newValue->getId() == $oldValue->getId());
        }
        
        /** In case of multiple objects relation */
        if(is_array($newValue)){
            
            if(sizeof($newValue) != sizeof($oldValue)){
                return false;
            }
            
            $match = true;
            
            foreach ($newValue as $key => $value) {
                if(!self::compareValues($value, $oldValue[$key])){                
                    $match = false;
                    break;
                }
            }
            
            return $match;
        }
        
        return ($newValue == $oldValue);
    }
}

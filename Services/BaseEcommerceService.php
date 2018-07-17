<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use Pimcore\Model\DataObject\Listing;

/**
 * Extending classes have to define their own functionality for custom attributes.
 * This is generic Shop level logic
 * Class EcommerceService
 */
abstract class BaseEcommerceService extends SingletonService{

    
    /**
     * Mapping for Object export
     * It builds the API array for communcation with object endpoint
     * 
     * @param $ecommObject the object to fill for the API call
     * @param $fieldMap the field map between Pimcore and external server
     * @param $fieldsDepth tree structure of the field in the API array
     * @param $language the active language
     * @param $dataSource the object to export
     * @param TargetServer $server the external server
     * @return array the API array
     * @throws \Exception
     */
    abstract protected function mapServerMultipleField($ecommObject, $fieldMap, $fieldsDepth, $language, $dataSource = null, TargetServer $server = null);
    
    
    /**
     * Return object listing of a specific class in respect to a specific condition.
     * In general case, object id is considered.
     * 
     * @param $objectId
     * @param $classname
     * @return Listing
     */
    protected function getObjectsToExport($objectId, $classname){
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\".$classname."\\Listing");
        $listing = $listingClass->newInstance();

        $listing->setCondition("oo_id = ".$listing->quote($objectId));
        return $listing;
    }

    protected function mapField(&$ecommObject, $serverField, $objectField){
        /**
         * Other special cases will be manage when needed
         */
        if($objectField instanceof \Pimcore\Model\DataObject\Data\QuantityValue){
            return $this->insertSingleValue($ecommObject, $serverField, $objectField->getValue());
        }else{
            return $this->insertSingleValue($ecommObject, $serverField, $objectField);
        }
    }

    protected function mapServerField ($apiObject, $serverFieldValue, $apiField) {
        // TODO: special cases managing here
        if($serverFieldValue instanceof \Pimcore\Model\DataObject\Data\QuantityValue){
            return $this->insertServerSingleField($apiObject, $serverFieldValue->getValue(), $apiField);
        }
        
        return $this->insertServerSingleField($apiObject, $serverFieldValue, $apiField);
    }

    protected function insertServerSingleField ($apiObject, $serverFieldValue, $apiField) {
        if (!array_key_exists($apiField, $apiObject)) {
            return $apiObject + [ $apiField => $serverFieldValue ];
        }
        return $apiObject;
    }
    
    /**
     * get field definition from field map.
     * 
     * If the field is a reference to another object
     * retrieve the related object specific field.
     * If no related field is set for a reference object
     * an exception will be throw
     * 
     * @param FieldMapping $fieldMap the field map
     * @param $language the server languages
     * @param $dataObject the object to sync
     */
    protected function getObjectField(FieldMapping $fieldMap, $language, $dataObject){
        $objectField = $fieldMap->getObjectField();
        
        $fieldType = $fieldMap->getFieldType();
        if ($fieldType == "reference") {
            $relatedField = $fieldMap->getRelatedField();
            
            if($relatedField == null || empty($relatedField)){
                throw new \Exception("ERROR - Related field must be defined for reference field '$objectField'");
            }
            
            $objectReflection = new \ReflectionObject($dataObject);
            $methodName = "get". ucfirst($objectField);
            $objectMethod = $objectReflection->getMethod($methodName);
            
            $relatedObject = $objectMethod->invoke($dataObject);
            
            return $this->getField($relatedField, $language, $relatedObject);
        }else{
            return $this->getField($objectField, $language, $dataObject);
        }
        
    }
    
    /**
     * Get the field value of the object.
     * check if field is localized and, if yes, take the right translation.
     * 
     * @param type $field the field of a class to get (format e.g. product_sku)
     * @param type $language the language of translation (if needed)
     * @param type $dataObject the object to get value of
     * 
     * @return the field value
     */
    private function getField($field, $language, $dataObject){
        if($dataObject == null){
            return "";
        }
        
        $objectReflection = new \ReflectionObject($dataObject);
        
        $classname = $dataObject->getClassName();
        
        $count=1;
        $fieldname = str_replace(strtolower($classname)."_", "", $field, $count);
        $methodName = "get". ucfirst($fieldname);
        
        $method = new \ReflectionMethod("\\Pimcore\\Model\\DataObject\\$classname",$methodName);
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
        
        $objectMethod = $objectReflection->getMethod($methodName);
        
        if($isLocalized && !empty($language)){
            $fieldValue = $objectMethod->invoke($dataObject, $language);
        }else{
            $fieldValue = $objectMethod->invoke($dataObject);
        }
        
        return $fieldValue;
    }
}
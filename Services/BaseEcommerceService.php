<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Logger;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;

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

    //$isBrick will be removed
    abstract protected function insertSingleValue(&$ecommObject, $fieldName, $fieldvalue, $isBrick = false);

    public function mapField(&$ecommObject, $serverField, $objectField){
        /**
         * Other special cases will be manage when needed
         */
        if($objectField instanceof \Pimcore\Model\DataObject\Data\QuantityValue){
            $this->insertSingleValue($ecommObject, $serverField, $objectField->getValue());
        }else{
            $this->insertSingleValue($ecommObject, $serverField, $objectField);
        }
    }
    
    /**
     * Retrieve $dataObject's Fieldcollection related to $targetServer
     * searching in $dataObject's exportServers field
     * 
     * @param $dataObject the object to sync
     * @param TargetServer $targetServer the server to sync object in
     * 
     * @return ServerObjectInfo
     */
    protected function getServerObjectInfo($dataObject, TargetServer $targetServer){
        
        $exportServers = $dataObject->getExportServers()->getItems();
        
        $server = $exportServers[
            array_search(
                    $targetServer->getKey(), 
                    array_column($exportServers, "name")
            )
        ];
        
        return $server;
    }
    
    /**
     * get field definition from field map
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
                throw new Exception("ERROR - Related field must be defined for reference field '$objectField'");
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
    
    private function getField($fieldName, $language, $dataObject){
        $objectReflection = new \ReflectionObject($dataObject);
        
        if(strpos($fieldName, "__") > -1){
            $fieldParts = explode("__", $fieldName);
            
            $relatedClass = ucfirst($fieldParts[0]);
            $relatedField = $fieldParts[1];
        }
        
        $classname = $dataObject->getClassName();
        
        $methodName = "get". ucfirst($fieldName);
        
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
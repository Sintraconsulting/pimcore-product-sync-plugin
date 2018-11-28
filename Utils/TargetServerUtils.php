<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\Fieldcollection\Data\FieldMapping;
use Pimcore\Model\DataObject\TargetServer;

/**
 * Target Server Utils
 *
 * @author Sintra Consulting
 */
class TargetServerUtils {
    
    /**
     * get fields to export for the object class
     * 
     * @param TargetServer $targetServer the server
     * @param $classname the class name
     * @return array fields to export
     */
    public static function getClassExportFields(TargetServer $targetServer, $classname){
        if(!$targetServer->getEnabled() || $targetServer->getExportFields() == null){
            return null;
        }
        
        $exportFieldsInfos = $targetServer->getExportFields()->getItems();
        
        $exportFields= null;
        
        foreach ($exportFieldsInfos as $exportFieldsInfo) {
            if($exportFieldsInfo->getExport_class() == $classname){
                $exportFields = $exportFieldsInfo->getFields();
                break;
            }
        }
        
        return $exportFields;
    }
    
    
    /**
     * get fields map for the object class
     * 
     * @param TargetServer $targetServer the server
     * @param $classname the class name
     * @return FieldMapping[] fields map
     */
    public static function getClassFieldMap(TargetServer $targetServer, $classname){
        $fieldsMapCollection = $targetServer->getFieldsMap()->getItems();
        
        $fieldsMap = array();
        
        foreach ($fieldsMapCollection as $fieldMap) {
            if($fieldMap->getExportClass() == $classname){
                $fieldsMap[] = $fieldMap;
            }
        }
        
        return $fieldsMap;
    }
}

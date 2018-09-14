<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\TargetServer;

/**
 * Target Server Utils
 *
 * @author Marco Guiducci
 */
class GeneralUtils {
    
    /**
     * Get all object classes defined in Pimcore
     * @return array
     */
    public static function getAvailableClasses(){
        $availableClasses = array();
        
        $classesList = new ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();
        
        foreach ($classes as $class) {
            $classname = $class->getName();
            
            if($classname != "TargetServer"){
                $availableClasses[] = $classname;
            }
        }
        
        return $availableClasses;
    }
    
    
    /**
     * Retrieve $dataObject's Fieldcollection related to $targetServer
     * searching in $dataObject's exportServers field
     * 
     * @param $dataObject object to sync
     * @param TargetServer $targetServer the server to sync object in
     * 
     * @return ServerObjectInfo
     */
    public static function getServerObjectInfo($dataObject, TargetServer $targetServer){
        
        $exportServers = $dataObject->getExportServers()->getItems();
        
        $server = $exportServers[
            array_search(
                    $targetServer->getKey(), 
                    array_column($exportServers, "name")
            )
        ];
        
        return $server;
    }
}
<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ImageInfo;
use Pimcore\Model\DataObject\Fieldcollection\Data\ExternalImageInfo;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;

/**
 * General Utils
 *
 * @author Sintra Consulting
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
    
    /**
     * Get ImagesInfo for the object
     * 
     * @param Concrete $dataObject
     * @return ImageInfo[]|ExternalImageInfo[]
     */
    public static function getObjectImagesInfo($dataObject){
        
        if(method_exists($dataObject, "getImages")){
            $images = $dataObject->getImages() != null ? $dataObject->getImages() : new Fieldcollection();
            return $images->getItems();
        }
        
        return array();
    }
}

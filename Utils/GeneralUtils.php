<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\ClassDefinition;

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
}

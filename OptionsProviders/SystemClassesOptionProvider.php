<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Dynamic Options Provider that display valid languages for a TargetServer.
 * Languages are taken from Pimcore configuration
 *
 * @author Marco Guiducci
 */
class SystemClassesOptionProvider implements SelectOptionsProviderInterface{

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }
    
    public function getOptions($context, $fieldDefinition): array {
        $fields = array();
        
        $classesList = new ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();
        
        foreach ($classes as $class) {
            $classname = $class->getName();
            
            if($classname != "TargetServer"){
                $fields[] = array(
                    "key" => $classname,
                    "value" => $classname
                );
            }
        }
        
        return $fields;
    }

    public function getDefaultValue($context, $fieldDefinition) {
        
    }

}

<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use SintraPimcoreBundle\Utils\GeneralUtils;

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
        $fields = [];
        
        foreach (GeneralUtils::getAvailableClasses() as $classname) {
            
            $fields[] = array(
                "key" => $classname,
                "value" => $classname
            );
        }
        
        return $fields;
    }

    public function getDefaultValue($context, $fieldDefinition) {
        
    }

}

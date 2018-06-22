<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Dynamic Options Provider for Product's category_ids field 
 *
 * @author Marco Guiducci
 */
class TargetServerExportFieldProvider implements MultiSelectOptionsProviderInterface{

    public function getOptions($context, $fieldDefinition): array {
        $fields = array();
        
        $productClass = ClassDefinition::getByName("Product");
        foreach ($productClass->getFieldDefinitions() as $fieldDefinition) {
            $fields[] = array("key" => $fieldDefinition->getTitle(), "value" => $fieldDefinition->getName());
        }
        
        return $fields;
    }

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }

}

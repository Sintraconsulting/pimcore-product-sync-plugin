<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * List Product fields
 *
 * @author Marco Guiducci
 */
class TargetServerExportFieldProvider implements MultiSelectOptionsProviderInterface{

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }
    
    public function getOptions($context, $fieldDefinition): array {
        $fields = array();
        
        $classDefinition = ClassDefinition::getByName("Product");
        $this->extractClassField($classDefinition, $fields);
        
        return $fields;
    }

    /**
     * Extract fields of a class.
     * 
     * @param ClassDefinition $classDefinition the class definition
     * @param array $fields array that will contains all fields
     */
    private function extractClassField($classDefinition, &$fields){
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            switch ($fieldDefinition->getFieldtype()){
                case "localizedfields":
                    foreach($fieldDefinition->getChilds() as $localizedFieldDefinition){
                        $fields[] = $this->extractSingleOption($localizedFieldDefinition, $classDefinition);
                    };
                    break;
                    
                case "objectbricks":
                case "fieldcollections":
                    break;
            
                default:
                    $fields[] = $this->extractSingleOption($fieldDefinition, $classDefinition);
                    break;
            }
        }
    }
    
    /**
     * create a new option entry for each field.
     * 
     * @param mixed $fieldDefinition the field definition
     * @param ClassDefinition $classDefinition the class definition
     */
    private function extractSingleOption($fieldDefinition, $classDefinition){
        $key = $fieldDefinition->getTitle();
        $value = $fieldDefinition->getName();
        
        return array(
            "key" => $key,
            "value" => $value
        );
    }

}

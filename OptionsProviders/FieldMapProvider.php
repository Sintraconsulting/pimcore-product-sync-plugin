<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * List Product fields
 *
 * @author Marco Guiducci
 */
class FieldMapProvider implements SelectOptionsProviderInterface{

    public function hasStaticOptions($context, $fieldDefinition): bool {
        return true;
    }

    public function getDefaultValue($context, $fieldDefinition) {
        return null;
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
                
                //get all localized fields
                case "localizedfields":
                    foreach($fieldDefinition->getChilds() as $localizedFieldDefinition){
                        $fields[] = $this->extractSingleOption($localizedFieldDefinition, $classDefinition);
                    };
                    break;
                
                //escape ObjectBricks and FieldCollections
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
            "key" => $isClassRelated ? $classDefinition->getName()." - ".$key : $key,
            "value" => $isClassRelated ? strtolower($classDefinition->getName())."__".$value : $value
        );
    }    

}

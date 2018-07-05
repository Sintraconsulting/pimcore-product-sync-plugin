<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldset;

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
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $this->extractClassField($fieldDefinition, $fields);
        }
        
        return $fields;
    }

    /**
     * Extract fields of a class.
     * 
     * @param $fieldDefinition the field definition
     * @param array $fields array that will contains all fields
     */
    private function extractClassField($fieldDefinition, &$fields){
        if($fieldDefinition instanceof Localizedfields){
            foreach ($fieldDefinition->getChildren() as $localizedFieldDefinition) {
                $this->extractClassField($localizedFieldDefinition, $fields);
            }
            
        }else if($fieldDefinition instanceof Fieldset){
            foreach ($fieldDefinition->getChildren() as $FieldsetFieldDefinition) {
                $this->extractClassField($FieldsetFieldDefinition, $fields);
            }
            
        }else if($fieldDefinition instanceof Fieldcollections){
            //nothig ToDo here
            
        }else if($fieldDefinition instanceof Objectbricks){
            //nothig ToDo here
            
        }else{
            $fields[] = $this->extractSingleOption($fieldDefinition);
        }
    }
    
    /**
     * create a new option entry for each field.
     * 
     * @param mixed $fieldDefinition the field definition
     */
    private function extractSingleOption($fieldDefinition){
        \Pimcore\Logger::info(print_r($fieldDefinition,true));
        
        $key = $fieldDefinition->getTitle();
        $value = $fieldDefinition->getName();
        
        return array(
            "key" => $key,
            "value" => $value
        );
    }  

}

<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
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
class TargetServerExportFieldProvider implements MultiSelectOptionsProviderInterface{

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
                $this->extractFields($classname, $fields);
            }
        }
        
        return $fields;
    }
    
    private function extractFields($classname, &$fields){
        $classDefinition = ClassDefinition::getByName($classname);
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            $this->extractClassField($classname, $fieldDefinition, $fields);
        }
    }

    /**
     * Extract fields of a class.
     * 
     * @param $classname the class name
     * @param $fieldDefinition the field definition
     * @param array $fields array that will contains all fields
     */
    private function extractClassField($classname, $fieldDefinition, &$fields){
        if($fieldDefinition instanceof Localizedfields){
            foreach ($fieldDefinition->getChildren() as $localizedFieldDefinition) {
                $this->extractClassField($classname, $localizedFieldDefinition, $fields);
            }
            
        }else if($fieldDefinition instanceof Fieldset){
            foreach ($fieldDefinition->getChildren() as $FieldsetFieldDefinition) {
                $this->extractClassField($classname, $FieldsetFieldDefinition, $fields);
            }
            
        }else if($fieldDefinition instanceof Fieldcollections){
            //nothig ToDo here
            
        }else if($fieldDefinition instanceof Objectbricks){
            //nothig ToDo here
            
        }else{
            $fields[] = $this->extractSingleOption($classname, $fieldDefinition);
        }
    }
    
    /**
     * create a new option entry for each field.
     * 
     * @param $classname the class name
     * @param mixed $fieldDefinition the field definition
     */
    private function extractSingleOption($classname, $fieldDefinition){
        
        $key = strtoupper($classname)." - ".$fieldDefinition->getTitle();
        $value = strtolower($classname)."_".$fieldDefinition->getName();
        
        return array(
            "key" => $key,
            "value" => $value
        );
    }

}

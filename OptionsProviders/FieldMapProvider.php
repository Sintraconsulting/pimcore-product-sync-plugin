<?php

namespace SintraPimcoreBundle\OptionsProviders;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Dynamic Options Provider for Product's category_ids field 
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

    private function extractClassField($classDefinition, &$fields, $isClassRelated = false){
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            switch ($fieldDefinition->getFieldtype()){
                case "localizedfields":
                    foreach($fieldDefinition->getChilds() as $localizedFieldDefinition){
                        $fields[] = $this->extractSingleOption($localizedFieldDefinition, $classDefinition, $isClassRelated);
                    };
                    break;
                    
                case "href":
                case "objects":
                    foreach($fieldDefinition->getClasses() as $classDefinition){
                        $relatedClass = $classDefinition["classes"];
                        $relatedClassDefinition = ClassDefinition::getByName($relatedClass);
                        
                        $this->extractClassField($relatedClassDefinition, $fields, true);
                    };
                    
                case "fieldcollections":
                    break;
            
                default:
                    $fields[] = $this->extractSingleOption($fieldDefinition, $classDefinition, $isClassRelated);
                    break;
            }
        }
    }
    
    private function extractSingleOption($fieldDefinition, $classDefinition, $isClassRelated){
        $key = $fieldDefinition->getTitle();
        $value = $fieldDefinition->getName();
        
        return array(
            "key" => $isClassRelated ? $classDefinition->getName()." - ".$key : $key,
            "value" => $isClassRelated ? $classDefinition->getName()."__".$value : $value
        );
    }    

}

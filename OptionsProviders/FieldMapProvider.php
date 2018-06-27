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
     * If a field is a reference to a different class object (or multiple objects)
     * do a recursion taking all fields of the referenced class
     * 
     * @param ClassDefinition $classDefinition the class definition
     * @param array $fields array that will contains all fields
     * @param boolean $isClassRelated specify if class is the main (Product) ora a related one
     */
    private function extractClassField($classDefinition, &$fields, $isClassRelated = false){
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            switch ($fieldDefinition->getFieldtype()){
                
                //get all localized fields
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
                
                //escape ObjectBricks and FieldCollections
                case "objectbricks":
                case "fieldcollections":
                    break;
            
                default:
                    $fields[] = $this->extractSingleOption($fieldDefinition, $classDefinition, $isClassRelated);
                    break;
            }
        }
    }
    
    /**
     * create a new option entry for each field.
     * For a related class, class name will be added in the option value.
     * 
     * E.g
     * The option value for the "description" field of the "Color" class will be:
     * "color__description"
     * 
     * @param mixed $fieldDefinition the field definition
     * @param ClassDefinition $classDefinition the class definition
     * @param boolean $isClassRelated specify if class is the main (Product) ora a related one
     */
    private function extractSingleOption($fieldDefinition, $classDefinition, $isClassRelated){
        $key = $fieldDefinition->getTitle();
        $value = $fieldDefinition->getName();
        
        return array(
            "key" => $isClassRelated ? $classDefinition->getName()." - ".$key : $key,
            "value" => $isClassRelated ? strtolower($classDefinition->getName())."__".$value : $value
        );
    }    

}

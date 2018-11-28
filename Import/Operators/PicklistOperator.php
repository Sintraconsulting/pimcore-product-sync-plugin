<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Operator for single value picklists 
 *
 * @author Sintra Consulting
 */
class PicklistOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Search the picklist entry by the display name 
     * and retrieve the corresponding picklist value
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $value = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $this->getValueByDisplayName($target->getClass(), $field, $value));

    }
    
    public function getValueByDisplayName(ClassDefinition $class, $field, $displayName){
        $fieldDefinition = $class->getFieldDefinition($field);
        
        if(in_array($fieldDefinition->getFieldtype(), array("select", "multiselect"))){
            $options = $fieldDefinition->getOptions();
            $option = array_search(strtolower($displayName), array_map('strtolower', array_column($options, "key")));
            return $option !== false ? $options[$option]["value"] : null;
        }
        
        return null;
    }

}

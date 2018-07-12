<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Operator for picklists 
 *
 * @author Marco Guiducci
 */
class PicklistOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Dynamically invoke field setter for quantityValue fields 
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

<?php

namespace SintraPimcoreBundle\Import\Operators;

/**
 * Operator for multiple picklists 
 *
 * @author Marco Guiducci
 */
class MultiplePicklistOperator extends PicklistOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Dynamically invoke field setter for picklist fields 
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $values = explode(",", $rowData[$colIndex]);
        $field = $this->additionalData["field"];
        
        $fieldValues = array();
        foreach ($values as $value) {
            $fieldValues[] = $this->getValueByDisplayName($target->getClass(), $field, trim($value));
        }
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $fieldValues);

    }

}

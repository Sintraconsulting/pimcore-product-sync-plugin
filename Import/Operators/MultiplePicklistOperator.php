<?php

namespace SintraPimcoreBundle\Import\Operators;

/**
 * Operator for multiple values picklists 
 *
 * @author Sintra Consulting
 */
class MultiplePicklistOperator extends PicklistOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * For each value, search the picklist entry by the display name 
     * and retrieve the corresponding picklist value
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        $separator = $this->additionalData["separator"] |= null 
                && in_array($this->additionalData["separator"], array(",",";","|","/")) ? $this->additionalData["separator"] : ",";
        
        $values = explode($separator, $rowData[$colIndex]);
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

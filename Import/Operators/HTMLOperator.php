<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
/**
 * Operator that performs escape of HTML tags
 *
 * @author Sintra Consulting
 */
class HTMLOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Get the column value and excape the HTML tags.
     * Properly set the obtained string to the specific field 
     * passed as additional data for the operator
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $text = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $this->escapeHTML($text));

    }
    
    public function escapeHTML($text){
        return html_entity_decode($text);
    }

}

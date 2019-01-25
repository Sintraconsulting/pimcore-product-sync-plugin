<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use SintraPimcoreBundle\Utils\GeneralUtils;

/**
 * Operator that performs transliteration of a string
 *
 * @author Sintra Consulting
 */
class TransliterateOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Get the column value and transliterate it.
     * Properly set the obtained string to the specific field 
     * passed as additional data for the operator.
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $text = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, GeneralUtils::transliterate($text));

    }

}

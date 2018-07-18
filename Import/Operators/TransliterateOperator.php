<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Transliterator;
/**
 * Class providing method to transliterate
 *
 * @author Marco Guiducci
 */
class TransliterateOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Dynamically invoke field setter for fields to transliterate
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $text = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $this->transliterate($text));

    }
    
    public function transliterate($text) {
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', 
            Transliterator::FORWARD
        );

        return $transliterator->transliterate($text);
        
    }

}

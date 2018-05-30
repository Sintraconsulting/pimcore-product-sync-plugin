<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\TransliterateOperator;
/**
 * Convert category path in comma-separated list of categories magento ids
 *
 * @author Marco Guiducci
 */
class KeyOperator extends TransliterateOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $key = $rowData[$colIndex];
        $target->setKey($this->transliterate($key));
    }

}

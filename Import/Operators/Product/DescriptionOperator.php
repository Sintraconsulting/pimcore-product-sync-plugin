<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\HTMLOperator;
/**
 * Escape HTML tags for Description
 *
 * @author Marco Guiducci
 */
class DescriptionOperator extends HTMLOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $text = $rowData[$colIndex];
        $target->setDescription($this->escapeHTML($text));
    }

}

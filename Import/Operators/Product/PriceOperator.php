<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\QuantityValueOperator;
/**
 * Convert category path in comma-separated list of categories magento ids
 *
 * @author Marco Guiducci
 */
class PriceOperator extends QuantityValueOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $price = $rowData[$colIndex];
        $target->setPrice($this->validateUnit($target->getClass(), "price", $price));
    }

}

<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\PicklistOperator;
/**
 * Get Brand select Value by display name.
 *
 * @author Marco Guiducci
 */
class BrandOperator extends PicklistOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $brand = $rowData[$colIndex];
        $value = $this->getValueByDisplayName($target->getClass(), "brand", $brand);
        $target->setBrand($value);
    }

}

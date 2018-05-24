<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\PicklistOperator;
/**
 * Get Visibility select Value by display name.
 *
 * @author Marco Guiducci
 */
class VisibilityOperator extends PicklistOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $visibility = $rowData[$colIndex];
        $value = $this->getValueByDisplayName($target->getClass(), "visibility", $visibility);
        $target->setVisibility($value);
    }

}

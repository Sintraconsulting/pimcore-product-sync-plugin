<?php

namespace SintraPimcoreBundle\Import\Operators\Product;

use SintraPimcoreBundle\Import\Operators\PicklistOperator;
/**
 * Get Tax Class select Value by display name.
 *
 * @author Marco Guiducci
 */
class TaxClassOperator extends PicklistOperator{
    
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {
        $taxClass = $rowData[$colIndex];
        $value = $this->getValueByDisplayName($target->getClass(), "tax_class_id", $taxClass);
        $target->setTax_class_id($value);
    }

}

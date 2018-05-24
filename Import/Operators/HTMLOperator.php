<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
/**
 * Escape HTML tags 
 *
 * @author Marco Guiducci
 */
abstract class HTMLOperator extends AbstractOperator{
    
    public function escapeHTML($text){
        return html_entity_decode($text);
    }

}

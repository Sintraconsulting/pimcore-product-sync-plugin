<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Transliterator;
/**
 * Abstract Class providing method to transliterate
 *
 * @author Marco Guiducci
 */
abstract class TransliterateOperator extends AbstractOperator{
    
    public function transliterate($text) {
        $transliterator = Transliterator::createFromRules(
            ':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', 
            Transliterator::FORWARD
        );

        return $transliterator->transliterate($text);
        
    }

}

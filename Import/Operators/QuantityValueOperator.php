<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Logger;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Data\QuantityValue;

/**
 * Check if currency exists
 *
 * @author Marco Guiducci
 */
abstract class QuantityValueOperator extends AbstractOperator{
    
    private $defaultCurrencyId = 1;
    
    public function validateCurrency($price){
        $priceObject = new QuantityValue();
        
        if(!strpos($price, "_") > 0){
            $priceObject->setValue($price);
            $priceObject->setUnitId($this->getDefaulCurrency());
        }
        
        $priceParts = explode("_", $price);
        $currencyId  = $priceParts[1];
        
        if($this->checkCurrency($currencyId)){
            $priceObject->setValue($priceParts[0]);
            $priceObject->setUnitId($currencyId);
        }else{
            Logger::warning("CURRENCY WITH ID '$currencyId' DOESN'T EXIST. DEFAULT SELECTED");
            $priceObject->setValue($priceParts[0]);
            $priceObject->setUnitId($this->getDefaulCurrency());
        }
        
        return $priceObject;
    }
    
    private function checkCurrency($currencyId){
        $currency = Unit::getById($currencyId);
        if($currency != null){
            return true;
        }else{
            return false;
        }
    }
    
    private function getDefaulCurrency(){
        $defaultCurrency = Unit::getById($this->defaultCurrencyId);
        if($defaultCurrency != null){
            return $defaultCurrency->getId();
        }
        
        return "";
    }

}

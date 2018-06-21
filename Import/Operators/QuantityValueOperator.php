<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Logger;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Check if unit exists
 *
 * @author Marco Guiducci
 */
abstract class QuantityValueOperator extends AbstractOperator{
    
    public function validateUnit(ClassDefinition $class, $field, $value){
        $fieldDefinition = $class->getFieldDefinition($field);
                
        if($fieldDefinition->getFieldtype() == "quantityValue"){
            $quantityValueObject = new QuantityValue();

            $quantityValueParts = explode("_", $value);
        
            if(sizeof($quantityValueParts) === 1 || $this->checkCurrency($quantityValueParts[1])){
                $quantityValueObject->setValue($value);
                $quantityValueObject->setUnitId($this->getDefaulCurrency($fieldDefinition));
            }else{
                $quantityValueObject->setValue($quantityValueParts[0]);
                $quantityValueObject->setUnitId($quantityValueParts[1]);
            }
            
            return $quantityValueObject;
        }
        
        return null;
    }
    
    private function checkCurrency($unitId){
        $unit = Unit::getById($unitId);
        if($unit != null){
            return true;
        }else{
            Logger::warning("UNIT WITH ID '$unitId' DOESN'T EXIST. DEFAULT SELECTED");
            return false;
        }
    }
    
    private function getDefaulCurrency(\Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue $fieldDefinition){
        $defaultCurrency = $fieldDefinition->getDefaultUnit();
        if($defaultCurrency != null){
            return $defaultCurrency;
        }
        
        Logger::warning("FIELD '".$fieldDefinition->getName()."' HAS NO DEFAULT UNIT.");
        return "";
    }

}

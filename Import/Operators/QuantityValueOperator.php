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
class QuantityValueOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Dynamically invoke field setter for quantityValue fields 
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $value = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $this->validateUnit($target->getClass(), $field, $value));

    }
    
    public function validateUnit(ClassDefinition $class, $field, $value){
        $fieldDefinition = $class->getFieldDefinition($field);
                
        if($fieldDefinition->getFieldtype() == "quantityValue"){
            $quantityValueObject = new QuantityValue();

            $quantityValueParts = explode("_", $value);
        
            if(sizeof($quantityValueParts) === 1 || !$this->checkCurrency($quantityValueParts[1])){
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

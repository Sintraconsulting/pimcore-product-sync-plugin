<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Logger;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition;

/**
 * Operator for QuantityValue fields
 *
 * @author Sintra Consulting
 */
class QuantityValueOperator extends AbstractOperator{
    
    private $additionalData;
    
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData,true);
    }
    
    /**
     * Check if the value passed in the CSV correctly provide a valid
     * unit of measure (or currency) for the field.
     * If not, attach the default one.
     * Then, properly set the obtained value to the specific field 
     * passed as additional data for the operator.     * 
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {  
        
        $value = $rowData[$colIndex];
        $field = $this->additionalData["field"];
        
        $reflection = new \ReflectionObject($target);
        $setFieldMethod = $reflection->getMethod('set'. ucfirst($field));
        $setFieldMethod->invoke($target, $this->validateUnit($target->getClass(), $field, $value));

    }
    
    /**
     * QuantityValue in Pimcore is composed as 'value_unit'
     * If the passed value doesn't have a valid unit
     * the default one for the field is taken.
     */
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

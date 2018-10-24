<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\Folder;

/**
 * Relate Object fields to product
 *
 * @author Marco Guiducci
 */
class MultipleObjectsRelationOperator extends AbstractOperator{
    
    /**
     * class: the related object class name
     * sourcefield: field name in imported object class
     * relatedfield: field name in related object class
     * 
     * create_if_missing: create a new object if missing (true|false)
     * folder: folder containg related class objects (user for create new one)
     * descriptionfield_index: field index for created object description
     */
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

        $object = null;
        $values = explode("|",$rowData[$colIndex]);
        
        $class = $this->additionalData["class"];
        $sourcefield = $this->additionalData["sourcefield"];
        $relatedfield = $this->additionalData["relatedfield"];
        $createIfMissing = $this->additionalData["create_if_missing"];
        
        $objects = [];
        
        foreach ($values as $value){
            if(!empty(trim($value))){
                $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\".$class."\\Listing");
                $listing = $listingClass->newInstance();

                $listing->setCondition($relatedfield." = ".$listing->quote($value));
                $listing->setLimit(1);

                $listing = $listing->load();

                if(!$listing && $createIfMissing){
                    $object = $this->createNewObject($rowData, $value, $class, $relatedfield, $colIndex);
                }else{
                    $object = $listing[0];               
                }
                
                $objects[] = $object;
            }
        }
        
        $reflectionTarget = new \ReflectionObject($target);
        $setSourceFieldMethod = $reflectionTarget->getMethod('set'. ucfirst($sourcefield));
        
        $setSourceFieldMethod->invoke($target, $objects);
        
    }
    
    private function createNewObject($rowData, $value, $class, $relatedfield, $colIndex){
        $folder = $this->additionalData["folder"];
        $descriptionfieldIndex = $this->additionalData["descriptionfield_index"];
        
        $objectFolder = Folder::getByPath("/".$folder);

        $objectClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\".$class);
        $object = $objectClass->newInstance();

        $object->setParentId($objectFolder->getId());
        $object->setKey(str_replace("/", "-", $value));
        $object->setPublished(1);

        $reflectionObject = new \ReflectionObject($object);
        $setFieldMethod = $reflectionObject->getMethod('set'. ucfirst($relatedfield));
        $setFieldMethod->invoke($object, $value);

        $descriptionValue = $colIndex == $descriptionfieldIndex ? $value : trim($rowData[$descriptionfieldIndex]);

        $setDescriptionMethod = $reflectionObject->getMethod('setDescription');

        $config = \Pimcore\Config::getSystemConfig();
        $languages = explode(",",$config->general->validLanguages);
        foreach ($languages as $lang) {
            $setDescriptionMethod->invoke($object, $descriptionValue, $lang);
        }

        $object->save();
        
        return $object;
    }

}

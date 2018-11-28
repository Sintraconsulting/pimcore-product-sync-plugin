<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\DataObject\Import\ColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\Folder;

/**
 * Operator that performs relation of an object.
 *
 * @author Sintra Consulting
 */
class ObjectRelationOperator extends AbstractOperator {

    /**
     * class: the related object class name
     * folder: folder containg related class objects (user for create new one)
     * sourcefield: field name in imported object class
     * 
     * create_if_missing: create a new object if missing (true|false)
     * relatedfield: field name in related object class
     * 
     * descriptionfield_name: field name for created object description 
     * descriptionfield_index: field index for created object description
     */
    private $additionalData;

    public function __construct(\stdClass $config, $context = null) {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData, true);
    }

    /**
     * Use information passed in additional data to search the existence the related object.
     * If the object exists, it will be attached to the target object.
     * If not, it could be created and then attached.
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {

        $object = null;
        $value = trim($rowData[$colIndex]);

        $class = $this->additionalData["class"];
        $sourcefield = $this->additionalData["sourcefield"];
        $relatedfield = $this->additionalData["relatedfield"];
        $createIfMissing = $this->additionalData["create_if_missing"];

        if (!empty($value)) {
            $listing = $this->getObjectsListing($class, $relatedfield, $value);

            if ($listing) {
                $object = $listing[0];
            } else if ($createIfMissing) {
                $object = $this->createNewObject($value, $class, $relatedfield);
            }
        }
        $reflectionTarget = new \ReflectionObject($target);
        $setSourceFieldMethod = $reflectionTarget->getMethod('set' . ucfirst($sourcefield));
        $setSourceFieldMethod->invoke($target, $object);
    }

    /**
     * Search for the existence of an object based on class, fieldname and value
     */
    protected function getObjectsListing($class, $relatedfield, $value) {
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\" . $class . "\\Listing");
        $listing = $listingClass->newInstance();

        $listing->setCondition($relatedfield . " = " . $listing->quote($value));
        $listing->setLimit(1);

        return $listing->load();
    }

    /**
     * Create a new object of the specific class
     * The value in the CSV column will be used for object key creation
     * and to initialize a specific field of the object.
     * 
     * If defined in additional data, it's possible to take new object descritpion 
     * from a specific column and set it to a specific field.
     */
    protected function createNewObject($rowData, $value, $class, $relatedfield) {
        $folder = $this->additionalData["folder"];

        $objectFolder = Folder::getByPath("/" . $folder);

        $objectClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\" . $class);
        $object = $objectClass->newInstance();

        $object->setParentId($objectFolder->getId());
        $object->setKey(str_replace("/", "-", $value));
        $object->setPublished(1);

        $reflectionObject = new \ReflectionObject($object);
        $setFieldMethod = $reflectionObject->getMethod('set' . ucfirst($relatedfield));
        $setFieldMethod->invoke($object, $value);

        $descriptionfieldName = isset($this->additionalData["descriptionfield_name"]) ? $this->additionalData["descriptionfield_name"] : null;
        $descriptionfieldIndex = isset($this->additionalData["descriptionfield_index"]) ? $this->additionalData["descriptionfield_index"] : null;
        
        if ($descriptionfieldName !== null && $descriptionfieldName !== ""
                && $descriptionfieldIndex !== null && $descriptionfieldIndex !== ""
                && method_exists($object, 'set' . ucfirst($descriptionfieldName))) {
            
            $descriptionValue = trim($rowData[$descriptionfieldIndex]);

            $setDescriptionMethod = $reflectionObject->getMethod('set' . ucfirst($descriptionfieldName));
            $setDescriptionMethod->invoke($object, $descriptionValue);
            
        }

        $object->save();

        return $object;
    }

}

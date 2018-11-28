<?php

namespace SintraPimcoreBundle\Import\Operators;

/**
 * Operator that performs relation of multiple objects.
 * Different objects should be divided in the CSV column by the "|" (pipe) character.
 *
 * @author Sintra Consulting
 */
class MultipleObjectsRelationOperator extends ObjectRelationOperator {

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
     * Get the list of objects to relate from the CSV column.
     * Attach each of them to the target object.
     */
    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {

        $values = explode("|", $rowData[$colIndex]);


        $sourcefield = $this->additionalData["sourcefield"];

        $objects = [];

        foreach ($values as $value) {
            if (!empty(trim($value))) {
                $this->attachObject($objects, $value);
            }
        }

        $reflectionTarget = new \ReflectionObject($target);
        $setSourceFieldMethod = $reflectionTarget->getMethod('set' . ucfirst($sourcefield));

        $setSourceFieldMethod->invoke($target, $objects);
    }

    /**
     * Use information passed in additional data to search the existence of each
     * of the related objects.
     * If an object exists, it will be attached to the target object.
     * If not, it could be created and then attached.
     */
    private function attachObject(array &$objects, $value) {
        $class = $this->additionalData["class"];
        $relatedfield = $this->additionalData["relatedfield"];
        $createIfMissing = $this->additionalData["create_if_missing"];

        $listing = $this->getObjectsListing($class, $relatedfield, $value);

        $object = null;
        if ($listing) {
            $object = $listing[0];
        } else if ($createIfMissing) {
            $object = $this->createNewObject($value, $class, $relatedfield);
        }

        if ($object !== null) {
            $objects[] = $object;
        }
    }

}

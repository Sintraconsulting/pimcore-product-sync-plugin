<?php

namespace SintraPimcoreBundle\Import\Operators;

use Pimcore\Model\DataObject\Category;
use SintraPimcoreBundle\Import\Operators\TransliterateOperator;

/**
 * Take a string representing a path of nested objects
 * Return the list of the objects found in the path
 * and set the list as a multiple reference in a field
 * 
 * class: the class of objects in the path
 * folder: the folder by which the path starts
 * field: the field in which the founded object must be related
 *
 * @author Marco Guiducci
 */
class ObjectTreeOperator extends TransliterateOperator {

    private $additionalData;

    public function __construct(\stdClass $config, $context = null) {
        parent::__construct($config, $context);

        $this->additionalData = json_decode($config->additionalData, true);
    }

    public function process($element, &$target, array &$rowData, $colIndex, array &$context = array()) {

        $object = null;
        $objects = [];

        $class = $this->additionalData["class"];
        $folder = $this->additionalData["folder"];
        $field = $this->additionalData["field"];

        $objectTree = explode('>', $rowData[$colIndex]);
        $level = sizeof($objectTree);

        foreach ($objectTree as $i => $branch) {
            $objectTree[$i] = $this->transliterate($branch);
        }

        while ($level > 0) {

            $path = implode('/', $objectTree);

            $fullpath = '/' . $folder . '/' . $path;
            $object = $this->getObject($class, $fullpath);

            if ($object !== null) {
                $objects[] = $object;
            }

            array_pop($objectTree);
            $level = sizeof($objectTree);
        }

        $reflectionTarget = new \ReflectionObject($target);
        $setSourceFieldMethod = $reflectionTarget->getMethod('set' . ucfirst($field));

        $setSourceFieldMethod->invoke($target, array_reverse($objects));
    }

    /**
     * @param String $class Class of the Object
     * @param $path Path of the Object
     * @return Category
     */
    private function getObject($class, $path) {
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\" . $class . "\\Listing");
        $listing = $listingClass->newInstance();

        $listing->setCondition("CONCAT(o_path,o_key) = ?", $path);
        $listing->setLimit(1);

        $objects = $listing->load();

        if ($objects) {
            $object = $objects[0];
        } else {
            $object = null;
        }

        return $object;
    }

}

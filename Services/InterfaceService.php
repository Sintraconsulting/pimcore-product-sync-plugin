<?php

use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\Category;

interface InterfaceService {
    /**
     * @param Product|Category $dataObject
     * @return mixed
     */
    function export($dataObject);

    /**
     * @param Product|Category $dataObject
     * @param bool $update
     * @return mixed
     */
    function toEcomm($dataObject, bool $update = false);
}
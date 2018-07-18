<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Model\DataObject\TargetServer;

interface InterfaceService {
    /**
     * @param $productId
     * @param TargetServer $targetServer
     */
    function export($productId, TargetServer $targetServer);

    /**
     * Get the mapping of field to export from the server definition.
     * For localized fields, the first valid language will be used.
     *
     * @param $ecommObject
     * @param $dataObjects
     * @param TargetServer $targetServer
     * @param $classname
     * @param bool $update
     */
    function toEcomm(&$ecommObject, $dataObjects, TargetServer $targetServer, $classname, bool $update = false);
}
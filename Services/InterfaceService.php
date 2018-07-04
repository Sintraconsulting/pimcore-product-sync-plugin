<?php
namespace SintraPimcoreBundle\Services;

use Pimcore\Model\DataObject\TargetServer;

interface InterfaceService {
    /**
     * @param $dataObject
     * @param TargetServer $targetServer
     * @return mixed
     */
    function export($dataObject, TargetServer $targetServer);

    /**
     * @param $dataObject
     * TargetServer $targetServer
     * @param bool $update
     * @return mixed
     */
    function toEcomm(&$ecommObject, $dataObject, TargetServer $targetServer, bool $update = false);
}
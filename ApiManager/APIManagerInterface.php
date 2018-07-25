<?php

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

interface APIManagerInterface {
    
    public static function createEntity($entity, TargetServer $server);

    public static function getEntityByKey($entityKey, TargetServer $server);
    
    public static function deleteEntity($entityKey, TargetServer $server);
    
    public static function updateEntity($entityKey, $entity, TargetServer $server);
    
}

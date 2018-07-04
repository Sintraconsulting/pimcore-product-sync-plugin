<?php

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

interface APIManagerInterface {
    
    function createEntity($entity, TargetServer $server);

    function getEntityByKey($entityKey, TargetServer $server);
    
    function deleteEntity($entityKey, TargetServer $server);
    
    function updateEntity($entityKey, $entity, TargetServer $server);
    
}

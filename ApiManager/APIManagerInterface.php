<?php

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

/**
 * API Manager Interface 
 * Must be implemented for each class that performs API calls
 *
 * @author Sintra Consulting
 */
interface APIManagerInterface {
    
    /**
     * Create a new entity
     * 
     * @param mixed $entity the entity to create. Will be used in the API call body.
     * @param TargetServer $server the server in which the entity should be created.
     */
    public static function createEntity($entity, TargetServer $server);

    /**
     * Get an existent entity by Key
     * 
     * @param mixed $entityKey the key of the entity.
     * @param TargetServer $server the server in which the entity is.
     */
    public static function getEntityByKey($entityKey, TargetServer $server);
    
    /**
     * Delete an existent entity
     * 
     * @param mixed $entityKey the key of the entity to delete.
     * @param TargetServer $server the server in which the entity should be deleted.
     */
    public static function deleteEntity($entityKey, TargetServer $server);
    
    /**
     * Update an existent entity
     * 
     * @param mixed $entityKey the key of the entity to update.
     * @param mixed $entity the entity to update. Will be used in the API call body
     * @param TargetServer $server the server in which the entity should be updated
     */
    public static function updateEntity($entityKey, $entity, TargetServer $server);
    
}

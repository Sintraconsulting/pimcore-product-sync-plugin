<?php

namespace SintraPimcoreBundle\Import\Resolvers;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;

/**
 * Generic object resolver
 *
 * @author Sintra Consulting
 */
class RelationResolver extends AbstractResolver{
    /**
     * Given a class and a field in additional data
     * check the existence of an object of the defined class for which
     * the passed field equals to the CSV column value.
     * 
     * If not exists, it will be created
     */
    public function resolve(\stdClass $config, int $parentId, array $rowData){
        $params = json_decode($config->resolverSettings->params,true);
        
        $columnId = $this->getIdColumn($config);
        $objectId = trim($rowData[$columnId]);
        
        $class = $params["class"];
        $field = $params["field"];
        
        $listingClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\$class\\Listing");
        
        $listing = $listingClass->newInstance();
        $listing->setCondition("$field = ".$listing->quote($objectId));
        $listing->setLimit(1);
        
        $objects = $listing->load();
        
        if($objects){
            $object = $listing[0];
        }else{
            $objectClass = new \ReflectionClass("\\Pimcore\\Model\\DataObject\\$class");
            
            $object = $objectClass->newInstance();
            $object->setParentId($parentId);
            $object->setKey(str_replace("/", "\\", $objectId));
            $object->setPublished(1);
            
            $setIdMethod = $objectClass->getMethod("set".ucfirst($field));
            $setIdMethod->invoke($object,$objectId);
        }
        
        return $object;
    }
}

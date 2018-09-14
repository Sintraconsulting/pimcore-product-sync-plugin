<?php

namespace SintraPimcoreBundle\Import\Resolvers;

use Pimcore\DataObject\Import\Resolver\AbstractResolver;

/**
 * Resolver for Href and Objects Relations
 *
 * @author Marco Guiducci
 */
class RelationResolver extends AbstractResolver{
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
        
        $listing = $listing->load();
        
        if($listing){
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

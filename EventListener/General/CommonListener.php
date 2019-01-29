<?php

namespace SintraPimcoreBundle\EventListener\General;

use Pimcore\Model\DataObject\Concrete;
use SintraPimcoreBundle\EventListener\InterfaceListener;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\ServerObjectInfo;
use SintraPimcoreBundle\Utils\EventListenerUtils;

/**
 * Implement methods for manage objects after events are fired.
 * This class is mainly use to keep updated synchronization information of objects
 * for the defined servers.
 * 
 * The 'preAddAction' attach all server instances in new objects.
 * The 'preUpdateAction' attach the missing ones in already existent objects
 * and check for fields changing to keep synchronization information updated.
 * 
 * @author Sintra Consulting
 */
class CommonListener extends ObjectListener implements InterfaceListener {

    /**
     * Get object's exportServers field collections
     * There will be a field collection for every server
     * in which the object must me syncronized.
     *
     * If the field collection for a specific server is missing for the object
     * it will be added so that all field collection are present.
     *
     * @param Concrete $dataObject the object to update
     */
    public function preAddAction($dataObject) {
        if (method_exists($dataObject, 'getExportServers')) {
            $exportServers = $dataObject->getExportServers() != null ? $dataObject->getExportServers() : new Fieldcollection();
            EventListenerUtils::insertMissingFieldCollections($exportServers);

            $dataObject->setExportServers($exportServers);
        }
    }

    /**
     * If the added object is a variant, mark the parent object
     * as to be synchronized
     *
     * @param Concrete $dataObject the object to update
     */
    public function postAddAction($dataObject) {

        $parent = $dataObject->getParent();
        
        if ($dataObject->getType() === AbstractObject::OBJECT_TYPE_VARIANT && method_exists($parent, 'getExportServers')) {
            EventListenerUtils::updateParentSynchronizationInfo($parent);
        }
    }

    /**
     * Firstly check if all server instances are present in the object.
     * If the field collection for a specific server is missing for the object
     * it will be added so that all field collection are present.
     * 
     * Then, invoke methods for checking if object fields changed
     * and properly update synchronization information for the object.
     * 
     * @param Concrete $dataObject the object to update
     */
    public function preUpdateAction($dataObject) {
        $this->setIsPublishedBeforeSave($dataObject->isPublished());

        if (method_exists($dataObject, 'getExportServers')) {
            /**
             * Get object's exportServers field collections
             * There will be a field collection for every server
             * in which the object must me syncronized.
             *
             * If the field collection for a specific server is missing for the object
             * it will be added so that all field collection are present.
             */
            $exportServers = $dataObject->getExportServers() != null ? $dataObject->getExportServers() : new Fieldcollection();
            EventListenerUtils::insertMissingFieldCollections($exportServers);


            /**
             * Load the previous version of object in order to check
             * if fields to export are changed in respect to the new values
             */
            $classname = $dataObject->getClassName();
            $getByIdMethod = new \ReflectionMethod("\\Pimcore\\Model\\DataObject\\" . $classname, "getById");

            $oldDataObject = $getByIdMethod->invoke(null, $dataObject->getId(), true);

            /**
             * For each server field changes evaluation is done separately
             * If at least a field to export in the server has changed,
             * mark the object as "to sync" for that server.
             * 
             * If at least one required field for the server is empty
             * mark the object as not completed for that server
             */
            foreach ($exportServers as $exportServer) {
                $this->updateServerObjectInfo($exportServer, $dataObject, $oldDataObject);
            }

            $dataObject->setExportServers($exportServers);
        }
    }

    /**
     * @param Concrete $dataObject
     */
    public function postUpdateAction($dataObject) {
        
    }

    /**
     * @param Concrete $dataObject
     */
    public function postDeleteAction($dataObject, $isUnpublished = false) {
        
        $parent = $dataObject->getParent();

        if ($dataObject->getType() === AbstractObject::OBJECT_TYPE_VARIANT && method_exists($parent, 'getExportServers')) {
            EventListenerUtils::updateParentSynchronizationInfo($parent);
        }
    }

    /**
     * Invoke methods for checking if:
     * - Some fields that has to be syncronized in a server are changed
     * - Images attached to the object are changed
     * - All required fields for the server are set.
     * 
     * @param ServerObjectInfo $exportServer the server information object
     * @param Concrete $dataObject the new version of the object
     * @param Concrete $oldDataObject the previous version of the object
     */
    private function updateServerObjectInfo(ServerObjectInfo &$exportServer, Concrete $dataObject, Concrete $oldDataObject) {
        $updated = false;
        
        if ($exportServer->getExport() && ($oldDataObject == null || EventListenerUtils::checkServerUpdate($exportServer, $dataObject, $oldDataObject))) {
            $exportServer->setSync(false);
            $updated = true;
        }

        if ($exportServer->getExport() && method_exists($dataObject, "getImages") && EventListenerUtils::checkImagesChanged($exportServer, $dataObject)) {
            $exportServer->setImages_sync(false);
            $exportServer->setSync(false);
            $updated = true;
        }

        $parent = $dataObject->getParent();

        if ($updated && $dataObject->getType() === AbstractObject::OBJECT_TYPE_VARIANT && method_exists($parent, 'getExportServers')) {
            EventListenerUtils::updateParentSynchronizationInfo($parent);
        }
        

        $complete = EventListenerUtils::checkObjectCompleted($exportServer, $dataObject);
        $exportServer->setComplete($complete);
    }

}

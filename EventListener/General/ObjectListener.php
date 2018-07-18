<?php

namespace SintraPimcoreBundle\EventListener\General;

use Pimcore\Model\DataObject\Concrete;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

/**
 * Implementation of ObjectListener
 * 
 * @author Marco Guiducci
 */
class ObjectListener extends AbstractObjectListener{
    
    protected $isPublishedBeforeSave;
    
    public function setIsPublishedBeforeSave($isPublishedBeforeSave){
        $this->isPublishedBeforeSave = $isPublishedBeforeSave;
    }
    
    /**
     * Dispatch the preAdd event to the specific class listener
     * If the object class is not managed for the preUpdate event, do nothing
     * For folder data objects the classname is null
     * 
     * @param Concrete $dataObject the object to update
     */
    public function preAddDispatcher($dataObject) {
        $className = strtolower($dataObject->o_className);

        switch ($className) {

            case null:
            case "targetserver":
                break;

            default:
                $productListener = new CommonListener();
                $productListener->preAddAction($dataObject);
                break;
        }
    }

    /**
     * Dispatch the preUpdate event to the specific class listener
     * If the object class is not managed for the preUpdate event, do nothing
     * For folder data objects the classname is null
     * 
     * @param Concrete $dataObject the object to update
     */
    public function preUpdateDispatcher($dataObject) {
        $className = strtolower($dataObject->o_className);

        switch ($className) {

            case null:
            case "targetserver":
                break;

            default:
                $productListener = new CommonListener();
                $productListener->preUpdateAction($dataObject);
                break;
        }
    }
    

    /**
     * Dispatch the postUpdate event to the specific class listener
     * If the object class is not managed for the postUpdate event, do nothing
     * For folder data objects the classname is null
     * 
     * @param Concrete $dataObject the updated object
     */
    public function postUpdateDispatcher($dataObject, $saveVersionOnly) {
        $className = strtolower($dataObject->o_className);

        switch ($className) {

            case null:
            case "targetserver":
                break;

            default:
                $productListener = new CommonListener();
                $productListener->postUpdateAction($dataObject);
                break;
        }
    }

    
    /**
     * Dispatch the postDelete event to the specific class listener
     * If the object class is not managed for the postDelete event, do nothing
     * 
     * @param Concrete $dataObject the deleted object
     */
    public function postDeleteDispatcher($dataObject) {
        
    }
}
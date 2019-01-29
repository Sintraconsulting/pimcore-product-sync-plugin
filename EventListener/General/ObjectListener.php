<?php

namespace SintraPimcoreBundle\EventListener\General;

use Pimcore\Model\DataObject\Concrete;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

/**
 * Extends the AbstractObjectListener and implements the dispatcher methods.
 * Each of these methods check for object class and dispatch the action to
 * the specific listener.
 *
 * @author Sintra Consulting
 */
class ObjectListener extends AbstractObjectListener{

    protected $isPublishedBeforeSave;

    public function setIsPublishedBeforeSave($isPublishedBeforeSave){
        $this->isPublishedBeforeSave = $isPublishedBeforeSave;
    }

    /**
     * Dispatch the preAdd event to the specific class listener
     * If the object class is not managed for the preAdd event, do nothing
     * For folder data objects the classname is null
     *
     * @param Concrete $dataObject the object to add
     */
    public function preAddDispatcher($dataObject) {
        if ($dataObject instanceof Concrete) {
            $className = strtolower($dataObject->getClassName());
        } else {
            $className = strtolower($dataObject->o_className);
        }

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
     * Dispatch the postAdd event to the specific class listener
     * If the object class is not managed for the postAdd event, do nothing
     * For folder data objects the classname is null
     *
     * @param Concrete $dataObject the object to add
     */
    public function postAddDispatcher($dataObject) {
        if ($dataObject instanceof Concrete) {
            $className = strtolower($dataObject->getClassName());
        } else {
            $className = strtolower($dataObject->o_className);
        }

        switch ($className) {

            case null:
            case "targetserver":
                break;

            default:
                $productListener = new CommonListener();
                $productListener->postAddAction($dataObject);
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
        if ($dataObject instanceof Concrete) {
            $className = strtolower($dataObject->getClassName());
        } else {
            $className = strtolower($dataObject->o_className);
        }

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
        if ($dataObject instanceof Concrete) {
            $className = strtolower($dataObject->getClassName());
        } else {
            $className = strtolower($dataObject->o_className);
        }

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
        if ($dataObject instanceof Concrete) {
            $className = strtolower($dataObject->getClassName());
        } else {
            $className = strtolower($dataObject->o_className);
        }

        switch ($className) {

            case null:
            case "targetserver":
                break;

            default:
                $productListener = new CommonListener();
                $productListener->postDeleteAction($dataObject);
                break;
        }
    }
}

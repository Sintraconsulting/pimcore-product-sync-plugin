<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Concrete;
use SintraPimcoreBundle\EventListener\General\ObjectListener;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

/**
 * Abstract class that invoke concrete listeners, passing the interested DataObject
 * Provide dispatcher function that those listeners must implement in order to 
 * properly dispatch the request to the right listener, based on object class.
 * 
 * @author Sintra Consulting
 */
abstract class AbstractObjectListener {

    /**
     * Properly dispatch the request to the right listener 
     * after the 'preAdd' event is fired
     * 
     * @param Concrete $dataObject
     */
    public abstract function preAddDispatcher($dataObject);
    
    /**
     * Properly dispatch the request to the right listener 
     * after the 'postAdd' event is fired
     * 
     * @param Concrete $dataObject
     */
    public abstract function postAddDispatcher($dataObject);

    /**
     * Properly dispatch the request to the right listener 
     * after the 'preUpdate' event is fired
     * 
     * @param Concrete $dataObject
     */
    public abstract function preUpdateDispatcher($dataObject);

    /**
     * Properly dispatch the request to the right listener 
     * after the 'postUpdate' event is fired
     * 
     * @param Concrete $dataObject
     */
    public abstract function postUpdateDispatcher($dataObject, $saveVersionOnly);

    /**
     * Properly dispatch the request to the right listener 
     * after the 'postDelete' event is fired
     * 
     * @param Concrete $dataObject
     */
    public abstract function postDeleteDispatcher($dataObject);

    /**
     * Invoke the dispatcher for the 'preAdd' event of the generic ObjectListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param DataObjectEvent $e the event
     */
    public static function onPreAdd(DataObjectEvent $e) {

        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();

            $objectListener = new ObjectListener();
            $objectListener->preAddDispatcher($obj);

            self::checkForCustomListener($obj, "onPreAdd","preAddDispatcher");
        }
    }
    
    /**
     * Invoke the dispatcher for the 'postAdd' event of the generic ObjectListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param DataObjectEvent $e the event
     */
    public static function onPostAdd(DataObjectEvent $e) {

        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();

            $objectListener = new ObjectListener();
            $objectListener->postAddDispatcher($obj);

            self::checkForCustomListener($obj, "onPostAdd","preAddDispatcher");
        }
    }

    /**
     * Invoke the dispatcher for the 'preUpdate' event of the generic ObjectListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param DataObjectEvent $e the event
     */
    public static function onPreUpdate(DataObjectEvent $e) {

        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();

            $objectListener = new ObjectListener();
            $objectListener->preUpdateDispatcher($obj);

            self::checkForCustomListener($obj, "onPreUpdate","preUpdateDispatcher");
        }
    }

    /**
     * Invoke the dispatcher for the 'postUpdate' event of the generic ObjectListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param DataObjectEvent $e the event
     */
    public static function onPostUpdate(DataObjectEvent $e) {

        if ($e instanceof DataObjectEvent) {
            $saveVersionOnly = $e->hasArgument("saveVersionOnly");
            $obj = $e->getObject();

            $objectListener = new ObjectListener();
            $objectListener->postUpdateDispatcher($obj, $saveVersionOnly);

            self::checkForCustomListener($obj, "onPostUpdate", "postUpdateDispatcher", $saveVersionOnly);
        }
    }

    /**
     * Invoke the dispatcher for the 'postDelete' event of the generic ObjectListener.
     * Then, check for the existence of a custom listener that extends 
     * the base functionalities
     * 
     * @param DataObjectEvent $e the event
     */
    public static function onPostDelete(DataObjectEvent $e) {

        if ($e instanceof DataObjectEvent) {
            $obj = $e->getObject();

            $objectListener = new ObjectListener();
            $objectListener->postDeleteDispatcher($obj);

            self::checkForCustomListener($obj, "onPostDelete","postDeleteDispatcher");
        }
    }

    /**
     * Check for the existence of a custom event listener
     * and invoke the specific event dispatcher
     * 
     * @param Concrete $obj
     * @param String $eventName
     */
    private static function checkForCustomListener($obj, $eventName, $dispatcherMethod, $saveVersionOnly = null) {
        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];

        if ($namespace != null && !empty($namespace)) {
            Logger::info("AbstractObjectListener - Custom $eventName Event for namespace: " . $namespace);
            $customObjectListenerClassName = '\\' . $namespace . '\\SintraPimcoreBundle\\EventListener\\ObjectListener';

            if (class_exists($customObjectListenerClassName)) {
                $customObjectListenerClass = new \ReflectionClass($customObjectListenerClassName);
                $customObjectListener = $customObjectListenerClass->newInstance();
                
                if($saveVersionOnly !== null){
                    $customObjectListener->$dispatcherMethod($obj,$saveVersionOnly);
                }else{
                    $customObjectListener->$dispatcherMethod($obj);
                }
            } else {
                Logger::warn("AbstractObjectListener - WARNING. Class not found: " . $customObjectListenerClass);
            }
        }
    }

}

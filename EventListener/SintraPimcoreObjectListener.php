<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

/**
 * Listen for Pimcore events related to DataObjects
 * For each event, invoke the abstract listener that will redirect
 * the event to specific listeners.
 * 
 * @author Sintra Consulting
 */
class SintraPimcoreObjectListener {
    
    /**
     * Listener for DataObject 'preAdd' event
     * @param DataObjectEvent $e the event
     */
    public function onPreAdd (DataObjectEvent $e) {
       AbstractObjectListener::onPreAdd($e);
    }
    
    /**
     * Listener for DataObject 'postAdd' event
     * @param DataObjectEvent $e the event
     */
    public function onPostAdd (DataObjectEvent $e) {
       AbstractObjectListener::onPostAdd($e);
    }
    
    /**
     * Listener for DataObject 'preUpdate' event
     * @param DataObjectEvent $e the event
     */
    public function onPreUpdate (DataObjectEvent $e) {
       AbstractObjectListener::onPreUpdate($e);
    }
    
    /**
     * Listener for DataObject 'postUpdate' event
     * @param DataObjectEvent $e the event
     */
    public function onPostUpdate (DataObjectEvent $e) {
       AbstractObjectListener::onPostUpdate($e);
    }
    
    /**
     * Listener for DataObject 'postDelete' event
     * @param DataObjectEvent $e the event
     */
    public function onPostDelete (DataObjectEvent $e) {
       AbstractObjectListener::onPostDelete($e);
    }
}
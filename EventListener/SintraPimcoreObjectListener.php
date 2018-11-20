<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

class SintraPimcoreObjectListener {
    
    public function onPreAdd (DataObjectEvent $e) {
       AbstractObjectListener::onPreAdd($e);
    }
    
    public function onPreUpdate (DataObjectEvent $e) {
       AbstractObjectListener::onPreUpdate($e);
    }
    
    public function onPostUpdate (DataObjectEvent $e) {
       AbstractObjectListener::onPostUpdate($e);
    }
    
    public function onPostDelete (DataObjectEvent $e) {
       AbstractObjectListener::onPostDelete($e);
    }
}
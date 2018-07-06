<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Logger;
use Pimcore\Event\Model\DataObjectEvent;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

class SintraPimcoreObjectListener {
    
    public function onPostAdd (DataObjectEvent $e) {
       AbstractObjectListener::onPostAdd($e);
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
<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Event\Model\AssetEvent;
use SintraPimcoreBundle\EventListener\Assets\AbstractAssetsListener;

/**
 * Listen for Pimcore events related to Asset
 * For each event, invoke the abstract listener that will redirect
 * the event to specific listeners.
 * 
 * @author Sintra Consulting
 */
class SintraPimcoreAssetsListener {
    
    /**
     * Listener for Asset 'postAdd' event
     * @param AssetEvent $e the event
     */
    public function onPostAdd (AssetEvent $e) {
       AbstractAssetsListener::onPostAdd($e);
    }
    
}
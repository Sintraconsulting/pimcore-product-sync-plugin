<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Event\Model\AssetEvent;
use SintraPimcoreBundle\EventListener\Assets\AbstractAssetsListener;

class SintraPimcoreAssetsListener {
    
    public function onPostAdd (AssetEvent $e) {
       AbstractAssetsListener::onPostAdd($e);
    }
    
}
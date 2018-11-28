<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use SintraPimcoreBundle\EventListener\Assets\AbstractAssetsListener;
use Pimcore\Model\Asset;

/**
 * Extends the AbstractAssetsListener and implements the dispatcher methods.
 * Each of these methods check for asset class and dispatch the action to
 * the specific listener.
 *
 * @author Sintra Consulting
 */
class AssetsListener extends AbstractAssetsListener{

    /**
     * Dispatch the postAdd event to the specific class listener
     * If the asset class is not managed for the postAdd event, do nothing
     * 
     * @param Asset $asset
     */
    public function postAddDispatcher($asset) {
        
        $type = strtolower($asset->getType());

        switch ($type) {
            case "image":
                $imageListener = new ImageListener();
                $imageListener->postAddAction($asset);
                break;
        }
    }
}

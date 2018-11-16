<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use SintraPimcoreBundle\EventListener\Assets\AbstractAssetsListener;
use Pimcore\Model\Asset;

/**
 * Implementation of AssetsListener
 *
 * @author Marco Guiducci
 */
class AssetsListener extends AbstractAssetsListener{

    /**
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

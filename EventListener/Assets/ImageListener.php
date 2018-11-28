<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Model\Asset\Image;
use SintraPimcoreBundle\Utils\AssetsEventListenerUtils;

/**
 * Implement methods for manage assets after events are fired.
 * 
 * @author Sintra Consulting
 */
class ImageListener extends AssetsListener implements AssetsInterfaceListener{


    /**
     * @param Image $asset
     */
    public function postAddAction($asset) {
        
    }

}

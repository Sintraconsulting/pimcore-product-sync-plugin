<?php

namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Model\Asset\Image;
use SintraPimcoreBundle\Utils\AssetsEventListenerUtils;

class ImageListener extends AssetsListener implements AssetsInterfaceListener{


    /**
     * @param Image $asset
     */
    public function postAddAction($asset) {
        
    }

}

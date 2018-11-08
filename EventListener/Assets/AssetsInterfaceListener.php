<?php
namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Model\Asset;

interface AssetsInterfaceListener {

    /**
     * @param Asset $asset
     */
    public function postAddAction($asset);
    
}
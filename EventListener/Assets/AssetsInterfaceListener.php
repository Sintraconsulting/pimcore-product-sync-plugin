<?php
namespace SintraPimcoreBundle\EventListener\Assets;

use Pimcore\Model\Asset;

/**
 * Interface that provide methods for manage Asset
 * when an event is fired and listened.
 * 
 * Must be implemented by each specific listener
 * 
 * @author Sintra Consulting
 */
interface AssetsInterfaceListener {

    /**
     * manage an object after the 'postAdd' event is fired
     * 
     * @param Asset $asset
     */
    public function postAddAction($asset);
    
}
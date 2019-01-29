<?php
namespace SintraPimcoreBundle\EventListener;

use Pimcore\Model\DataObject\Concrete;

/**
 * Interface that provide methods for manage DataObjects
 * when an event is fired and listened.
 * 
 * Must be implemented by each specific listener
 * 
 * @author Sintra Consulting
 */
interface InterfaceListener {
    
    /**
     * manage an object after the 'preAdd' event is fired
     * 
     * @param Concrete $dataObject
     */
    public function preAddAction($dataObject);
    
    /**
     * manage an object after the 'postAdd' event is fired
     * 
     * @param Concrete $dataObject
     */
    public function postAddAction($dataObject);
    
    /**
     * manage an object after the 'preUpdate' event is fired
     * 
     * @param Concrete $dataObject
     */
    public function preUpdateAction($dataObject);
    
    /**
     * manage an object after the 'postUpdate' event is fired
     * 
     * @param Concrete $dataObject
     */
    public function postUpdateAction($dataObject);
    
    /**
     * manage an object after the 'postDelete' event is fired
     * 
     * @param Concrete $dataObject
     */
    public function postDeleteAction($dataObject, $isUnpublished = false);
}
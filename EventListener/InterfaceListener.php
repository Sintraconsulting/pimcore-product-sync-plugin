<?php
namespace SintraPimcoreBundle\EventListener;

use Pimcore\Model\DataObject\Concrete;

interface InterfaceListener {
    /**
     * @param Concrete $dataObject
     */
    public function preAddAction($dataObject);
    
    /**
     * @param Concrete $dataObject
     */
    public function preUpdateAction($dataObject);
    
    /**
     * @param Concrete $dataObject
     */
    public function postUpdateAction($dataObject);
    
    /**
     * @param Concrete $dataObject
     */
    public function postDeleteAction($dataObject, $isUnpublished = false);
}
<?php
namespace SintraPimcoreBundle\EventListener;

use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\Category;

interface InterfaceListener {
    /**
     * @param Product $dataObject
     */
    public function postAddAction($dataObject);
    
    /**
     * @param Product|Category $dataObject
     */
    public function preUpdateAction($dataObject);
    
    /**
     * @param Product|Category $dataObject
     */
    public function postUpdateAction($dataObject);
    
    /**
     * @param Product|Category $dataObject
     */
    public function postDeleteAction($dataObject, $isUnpublished = false);
}
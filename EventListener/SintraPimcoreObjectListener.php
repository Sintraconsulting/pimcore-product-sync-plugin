<?php

namespace SintraPimcoreBundle\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\SintraPimcoreCategoryListener;
use SintraPimcoreBundle\EventListener\SintraPimcoreProductListener;

class SintraPimcoreObjectListener {
    private $isPublishedBeforeSave;
    
    public function onPreUpdate (ElementEventInterface $e) {
       
        if ($e instanceof DataObjectEvent) {
            // do something with the object
            $obj = $e->getObject();
            $objId = $obj->getId();
            
            $className = $obj->o_className;
            switch ($className) {
                case "category":
                    $category = Category::getById($objId,true);
                    $this->isPublishedBeforeSave = $category->isPublished();

                    break;
                
                case "product":
                    $product = Product::getById($objId,true);
                    $this->isPublishedBeforeSave = $product->isPublished();
                    
                    break;

                default:
                    break;
            }
        }
    }
    
    public function onPostUpdate (ElementEventInterface $e) {
       
        if ($e instanceof DataObjectEvent) {
            
            $saveVersionOnly = $e->hasArgument("saveVersionOnly");
            $obj = $e->getObject();
            $objId = $obj->getId();
            
            $isPublishedBeforeSave = $this->isPublishedBeforeSave;
            
            $className = $obj->o_className;
            switch ($className) {
                case "category":
                    $categoryListener = new SintraPimcoreCategoryListener();
                    $category = Category::getById($objId);
                    
                    $isPublished = $category->isPublished();

                    if($isPublishedBeforeSave && !$isPublished){
                        Logger::debug("SintraPimcoreObjectListener - Unpublished Category. Delete in Magento.");
                        $categoryListener->onPostDelete($obj, true);

                    }else if($saveVersionOnly || !$isPublished){
                        Logger::debug("SintraPimcoreObjectListener - Save Local Version Only.");

                    }else{
                        Logger::debug("SintraPimcoreObjectListener - Insert or Update Catgegory in Magento");
                        $categoryListener->onPostUpdate($category);
                    }

                    break;
                
                case "product":
                    $productListener = new SintraPimcoreProductListener();
                    $product = Product::getById($objId);
                    
                    $isPublished = $product->isPublished();
                    
                    if($isPublishedBeforeSave && !$isPublished){
                        Logger::debug("SintraPimcoreObjectListener - Unpublished Product. Delete in Magento.");
                        $productListener->onPostDelete($obj, true);
                        
                    }else if($saveVersionOnly || !$isPublished){
                        Logger::debug("SintraPimcoreObjectListener - Save Local Version Only.");
                    }else{
                        Logger::debug("SintraPimcoreObjectListener - Insert or Update Product in Magento");
                        $productListener->onPostUpdate($product);
                    }
                    
                    break;

                default:
                    Logger::debug("SintraPimcoreObjectListener - Class '".$className."' is not Managed for Update");
                    break;
            }
        }
    }
    
    public function onPostDelete (ElementEventInterface $e) {
       
        if ($e instanceof DataObjectEvent) {
            // do something with the object
            $obj = $e->getObject();
            
            $className = $obj->o_className;
            switch ($className) {
                case "category":
                    Logger::debug("SintraPimcoreObjectListener - Delete Catgegory in Magento");
                    
                    $categoryListener = new SintraPimcoreCategoryListener();
                    $categoryListener->onPostDelete($obj);

                    break;
                
                case "product":
                    Logger::debug("SintraPimcoreObjectListener - Delete Product in Magento");
                    
                    $productListener = new SintraPimcoreProductListener();
                    $productListener->onPostDelete($obj);
                    
                    break;

                default:
                    Logger::debug("SintraPimcoreObjectListener - Class '".$className."' is not Managed for Delete");
                    break;
            }
        }
    }
}
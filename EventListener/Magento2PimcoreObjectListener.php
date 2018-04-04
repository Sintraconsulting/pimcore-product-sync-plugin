<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use Magento2PimcoreBundle\EventListener\Magento2PimcoreCategoryListener;
use Magento2PimcoreBundle\EventListener\Magento2PimcoreProductListener;

class Magento2PimcoreObjectListener {
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
                    
                    $category->setMagento_syncronized(false);
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
                    $categoryListener = new Magento2PimcoreCategoryListener();
                    $category = Category::getById($objId);
                    
                    if($category->export_to_magento){
                    
                        $isPublished = $category->isPublished();

                        if($isPublishedBeforeSave && !$isPublished){
                            Logger::debug("Magento2PimcoreObjectListener - Unpublished Category. Delete in Magento.");
                            $categoryListener->onPostDelete($obj);

                        }else if($saveVersionOnly || !$isPublished){
                            Logger::debug("Magento2PimcoreObjectListener - Save Local Version Only.");

                        }else{
                            Logger::debug("Magento2PimcoreObjectListener - Insert or Update Catgegory in Magento");
                            $categoryListener->onPostUpdate($category);
                        }
                    }

                    break;
                
                case "product":
                    $productListener = new Magento2PimcoreProductListener();
                    $product = Product::getById($objId);
                    
                    $isPublished = $product->isPublished();
                    
                    if($isPublishedBeforeSave && !$isPublished){
                        Logger::debug("Magento2PimcoreObjectListener - Unpublished Product. Delete in Magento.");
                        $productListener->onPostDelete($obj);
                        
                    }else if($saveVersionOnly || !$isPublished){
                        Logger::debug("Magento2PimcoreObjectListener - Save Local Version Only.");
                    }else{
                        Logger::debug("Magento2PimcoreObjectListener - Insert or Update Product in Magento");
                        $productListener->onPostUpdate($product);
                    }
                    
                    break;

                default:
                    Logger::debug("Magento2PimcoreObjectListener - Class '".$className."' is not Managed for Update");
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
                    Logger::debug("Magento2PimcoreObjectListener - Delete Catgegory in Magento");
                    
                    $categoryListener = new Magento2PimcoreCategoryListener();
                    $categoryListener->onPostDelete($obj);

                    break;
                
                case "product":
                    Logger::debug("Magento2PimcoreObjectListener - Delete Product in Magento");
                    
                    $productListener = new Magento2PimcoreProductListener();
                    $productListener->onPostDelete($obj);
                    
                    break;

                default:
                    Logger::debug("Magento2PimcoreObjectListener - Class '".$className."' is not Managed for Delete");
                    break;
            }
        }
    }
}
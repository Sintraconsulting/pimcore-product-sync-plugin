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
    
    public function onPostUpdate (ElementEventInterface $e) {
       
        if ($e instanceof DataObjectEvent) {
            // do something with the object
            $obj = $e->getObject();
            
            $className = $obj->o_className;
            switch ($className) {
                case "category":
                    Logger::debug("Magento2PimcoreObjectListener - Update Catgegory");
                    
                    $categoryListener = new Magento2PimcoreCategoryListener();
                    $category = Category::getById($obj->getId());
                    $categoryListener->onPostUpdate($category);

                    break;
                
                case "product":
                    Logger::debug("Magento2PimcoreObjectListener - Update Product");
                    
                    $productListener = new Magento2PimcoreProductListener();
                    $product = Product::getById($obj->getId());
                    $productListener->onPostUpdate($product);
                    
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
                    Logger::debug("Magento2PimcoreObjectListener - Delete Catgegory");
                    
                    $categoryListener = new Magento2PimcoreCategoryListener();
                    $categoryListener->onPostDelete($obj);

                    break;
                
                case "product":
                    Logger::debug("Magento2PimcoreObjectListener - Delete Product");
                    
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
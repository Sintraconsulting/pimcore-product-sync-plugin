<?php

namespace Magento2PimcoreBundle\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Magento2PimcoreBundle\EventListener\Magento2PimcoreCategoryListener;

class Magento2PimcoreObjectListener {
     
    public function onPostAdd (ElementEventInterface $e) {
       
        if ($e instanceof DataObjectEvent) {
            // do something with the object
            $obj = $e->getObject();
            
            $className = $obj->o_className;
            switch ($className) {
                case "category":
                    Logger::debug("Magento2PimcoreObjectListener - Add Catgegory");
                    
                    $categoryListener = new Magento2PimcoreCategoryListener();
                    $category = Category::getById($obj->getId());
                    $categoryListener->onPostAdd($category);

                    break;
                
                case "product":
                    Logger::debug("Magento2PimcoreObjectListener - Add Product");
                    
                    break;

                default:
                    Logger::debug("Magento2PimcoreObjectListener - Class '".$className."' is not Managed for Add");
                    break;
            }
        }
    }
    
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
                    
                    break;

                default:
                    Logger::debug("Magento2PimcoreObjectListener - Class '".$className."' is not Managed for Delete");
                    break;
            }
        }
    }
}
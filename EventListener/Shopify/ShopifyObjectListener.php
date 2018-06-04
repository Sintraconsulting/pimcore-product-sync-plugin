<?php

namespace SintraPimcoreBundle\EventListener\Shopify;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\Shopify\ShopifyCategoryListener;
use SintraPimcoreBundle\EventListener\Shopify\ShopifyProductListener;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

class ShopifyObjectListener extends AbstractObjectListener{

    private $shopifyCategoryListener;
    private $shopifyProductListener;
    
    function __construct(){
        $this->shopifyCategoryListener = new ShopifyCategoryListener();
        $this->shopifyProductListener = new ShopifyProductListener();
    }
    
    /**
     * @param Product|Category $dataObject
     */
    public function preUpdateDispatcher($dataObject) {
        $className = $dataObject->o_className;
        $className = strtolower($className);

        switch ($className) {
            case "category":
                $shopifyCategoryListener = new ShopifyCategoryListener();
                $shopifyCategoryListener->preUpdateAction($dataObject);
                break;

            case "product":
                $shopifyProductListener = new ShopifyProductListener();
                $shopifyProductListener->preUpdateAction($dataObject);
                break;

            default:
                Logger::debug("ShopifyObjectListener - Class '".$className."' is not Managed for preUpdate");
                break;
        }
    }
    
    /**
     * @param Product|Category $dataObject
     */
    public function postUpdateDispatcher($dataObject, $saveVersionOnly) {
        
        $className = $dataObject->o_className;
        $className = strtolower($className);

        switch ($className) {
            case "category":
                $shopifyCategoryListener = new ShopifyCategoryListener();
                $shopifyCategoryListener->postUpdateAction($dataObject);
                break;

            case "product":
                $shopifyProductListener = new ShopifyProductListener();
                $shopifyProductListener->postUpdateAction($dataObject);
                break;

            default:
                Logger::debug("ShopifyObjectListener - Class '".$className."' is not Managed for postUpdate");
                break;
        }
    }
    
    /**
     * @param Product|Category $dataObject
     */
    public function postDeleteDispatcher($dataObject) {
        $className = $dataObject->o_className;
        $className = strtolower($className);

        switch ($className) {
            case "category":
                $shopifyCategoryListener = new ShopifyCategoryListener();
                $shopifyCategoryListener->postDeleteAction($dataObject);
                break;

            case "product":
                $shopifyProductListener = new ShopifyProductListener();
                $shopifyProductListener->postDeleteAction($dataObject);
                break;

            default:
                Logger::debug("ShopifyObjectListener - Class '".$className."' is not Managed for postDelete");
                break;
        }
    }

}
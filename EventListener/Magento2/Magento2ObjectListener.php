<?php

namespace SintraPimcoreBundle\EventListener\Magento2;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Category;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\EventListener\Magento2\Magento2CategoryListener;
use SintraPimcoreBundle\EventListener\Magento2\Magento2ProductListener;
use SintraPimcoreBundle\EventListener\AbstractObjectListener;

class Magento2ObjectListener extends AbstractObjectListener{
    protected $isPublishedBeforeSave;
    
    public function setIsPublishedBeforeSave($isPublishedBeforeSave){
        $this->isPublishedBeforeSave = $isPublishedBeforeSave;
    }

    public function postAddDispatcher($dataObject) {
        $className = $dataObject->o_className;
        $className = strtolower($className);
        
        Logger::debug("Magento2ObjectListener - Class '".$className."' is not Managed for preUpdate");
    }
    
    /**
     * @param Product|Category $dataObject
     */
    public function preUpdateDispatcher($dataObject) {
        $className = $dataObject->o_className;
        $className = strtolower($className);
        
        

        switch ($className) {
            case "category":
                $magento2CategoryListener = new Magento2CategoryListener();
                $magento2CategoryListener->preUpdateAction($dataObject);
                break;

            case "product":
                $magento2ProductListener = new Magento2ProductListener();
                $magento2ProductListener->preUpdateAction($dataObject);
                break;

            default:
                Logger::debug("Magento2ObjectListener - Class '".$className."' is not Managed for preUpdate");
                break;
        }
    }
    
    /**
     * @param Product|Category $dataObject
     */
    public function postUpdateDispatcher($dataObject, $saveVersionOnly) {
        
        $className = $dataObject->o_className;
        $className = strtolower($className);
        
        $isPublishedBeforeSave = $this->isPublishedBeforeSave;
        $isPublished = $dataObject->isPublished();

        switch ($className) {
            case "category":
                $magento2CategoryListener = new Magento2CategoryListener();
                
                if($isPublishedBeforeSave && !$isPublished){
                    Logger::debug("Magento2ObjectListener - Unpublished Product. Delete in Magento.");
                    $magento2CategoryListener->postDeleteAction($dataObject, true);

                }else if($saveVersionOnly || !$isPublished){
                    Logger::debug("Magento2ObjectListener - Save Local Version Only.");
                }else{
                    Logger::debug("Magento2ObjectListener - Insert or Update Product in Magento");
                    $magento2CategoryListener->postUpdateAction($dataObject);
                }
                
                break;

            case "product":
                
                $magento2ProductListener = new Magento2ProductListener();
                
                if($isPublishedBeforeSave && !$isPublished){
                    Logger::debug("Magento2ObjectListener - Unpublished Product. Delete in Magento.");
                    $magento2ProductListener->postDeleteAction($dataObject, true);

                }else if($saveVersionOnly || !$isPublished){
                    Logger::debug("Magento2ObjectListener - Save Local Version Only.");
                }else{
                    Logger::debug("Magento2ObjectListener - Insert or Update Product in Magento");
                    $magento2ProductListener->postUpdateAction($dataObject);
                }
                
                break;

            default:
                Logger::debug("Magento2ObjectListener - Class '".$className."' is not Managed for postUpdate");
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
                $magento2CategoryListener = new Magento2CategoryListener();
                $magento2CategoryListener->postDeleteAction($dataObject);
                break;

            case "product":
                $magento2ProductListener = new Magento2ProductListener();
                $magento2ProductListener->postDeleteAction($dataObject);
                break;

            default:
                Logger::debug("Magento2ObjectListener - Class '".$className."' is not Managed for postDelete");
                break;
        }
    }
}
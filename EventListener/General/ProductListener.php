<?php

namespace SintraPimcoreBundle\EventListener\General;

use Pimcore\Logger;
use Pimcore\Model\DataObject\Product;
use SintraPimcoreBundle\ApiManager\Mage2\Mage2ProductAPIManager;
use SintraPimcoreBundle\EventListener\InterfaceListener;

class ProductListener extends ObjectListener implements InterfaceListener{
    
    /**
     * Get product's exportServers field collections
     * There will be a field collection for every server 
     * in which the product must me syncronized.
     * 
     * If the field collection for a specific server is missing for the product
     * it will be added so that all field collection are present.
     * 
     * @param Product $product the product to update
     */
    public function preAddAction($product) {
        $exportServers = $product->getExportServers() != null ? $product->getExportServers() : new Fieldcollection();
        EventListenerUtils::insertMissingFieldCollections($exportServers);
        
        $product->setExportServers($exportServers);
    }

    
    /**
     * Implementation of preUpdate event for Product class.
     * 
     * @param Product $product the product to update
     */
    public function preUpdateAction($product) {
        $this->setIsPublishedBeforeSave($product->isPublished());
        
        /**
         * Get product's exportServers field collections
         * There will be a field collection for every server 
         * in which the product must me syncronized.
         * 
         * If the field collection for a specific server is missing for the product
         * it will be added so that all field collection are present.
         */
        $exportServers = $product->getExportServers() != null ? $product->getExportServers() : new Fieldcollection();
        EventListenerUtils::insertMissingFieldCollections($exportServers);
        
        
        /**
         * Load the previous version of product in order to check 
         * if fields to export are changed in respect to the new values
         */
        $oldProduct = Product::getById($product->getId(), true);

        /**
         * For each server field changes evaluation is done separately
         * If at least a field to export in the server has changed,
         * mark the product as "to sync" for that server.
         */
        foreach ($exportServers as $exportServer) {
            if($exportServer->getExport() && EventListenerUtils::checkServerUpdate($exportServer, $product, $oldProduct)){
                $exportServer->setSync(false);
            }
        }

        $product->setExportServers($exportServers);
    }

    /**
     * @param Product $product
     */
    public function postUpdateAction($product) {
        
    }

    /**
     * @param Product $product
     */
    public function postDeleteAction($product, $isUnpublished = false) {
        
    }
    

}

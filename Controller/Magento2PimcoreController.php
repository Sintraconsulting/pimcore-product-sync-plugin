<?php

namespace Magento2PimcoreBundle\Controller;

use Magento2PimcoreBundle\EventListener\Magento2PimcoreCategoryListener;
use Magento2PimcoreBundle\EventListener\Magento2PimcoreProductListener;
use Pimcore\Model\DataObject;
use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Cache;
use Pimcore\Controller\Controller;
use Pimcore\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Magento2PimcoreController
 * @package Magento2PimcoreBundle\Controller
 *
 * @Route("/magento2_pimcore")
 */
class Magento2PimcoreController extends Controller implements AdminControllerInterface {

    /**
     * @inheritDoc
     */
    public function needsSessionDoubleAuthenticationCheck()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function needsStorageDoubleAuthenticationCheck()
    {
        return false;
    }
    
    /**
     * @Route("/sync_magento_categories")
     */
    public function syncMagentoCategoriesAction(Request $request)
    {
        $categoryListener = new Magento2PimcoreCategoryListener();
        
        $categories = new DataObject\Category\Listing();
        $categories->addConditionParam("export_to_magento = ?", "1");
        $categories->addConditionParam("magento_syncronized = ?", "0");
        $categories->setLimit("30");
        
        $count = 0;
        $next = $categories->count() > 0;
        while($next){
            $category = $categories->current();
            
            try{
                $categoryListener->onPostUpdate($category);
            } catch(\Exception $e){
                Logger::err($e->getMessage());
            }
            
            $count++;
            $next = $categories->next();
        }
        
        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }
        
        $datetime = date("Y-m-d H:i:s");
        
        if($count > 0){
            Logger::debug("Sincronizzate correttamente $count categorie.");      
            return new Response("[$datetime] - Sincronizzate correttamente $count categorie.");
        }else{
            Logger::debug("Nessuna categoria da sincronizzare.");      
            return new Response("[$datetime] - Nessuna categoria da sincronizzare.");
        }
    }
    
    /**
     * @Route("/sync_magento_products")
     */
    public function syncMagentoProductsAction(Request $request)
    {
        $productListener = new Magento2PimcoreProductListener();
        
        $products = new DataObject\Product\Listing();
        $products->addConditionParam("export_to_magento = ?", "1");
        $products->addConditionParam("magento_syncronized = ?", "0");
        $products->setLimit("10");
        
        $count = 0;
        $next = $products->count() > 0;
        while($next){
            $product = $products->current();

            try{
                $productListener->onPostUpdate($product);
            } catch(\Exception $e){
                Logger::err($e->getMessage());
            }
            
            
            $count++;
            $next = $products->next();
        }
        
        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }
        
        $datetime = date("Y-m-d H:i:s");
        
        if($count > 0){
            Logger::debug("Sincronizzati correttamente $count prodotti.");      
            return new Response("[$datetime] - Sincronizzati correttamente $count prodotti.");
        }else{
            Logger::debug("Nessun prodotto da sincronizzare.");      
            return new Response("[$datetime] - Nessun prodotto da sincronizzare.");
        }
    }

}

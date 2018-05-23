<?php

namespace SintraPimcoreBundle\Controller;

use SintraPimcoreBundle\Services\Magento2CategoryService;
use SintraPimcoreBundle\Services\Magento2ProductService;
use Pimcore\Model\DataObject;
use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Cache;
use Pimcore\Controller\Controller;
use Pimcore\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SintraPimcoreController
 * @package SintraPimcoreBundle\Controller
 *
 * @Route("/sintra_pimcore")
 */
class SintraPimcoreController extends Controller implements AdminControllerInterface {

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
     * @Route("/sync_categories")
     */
    public function syncCategoriesAction(Request $request)
    {
        $categoryUtils = Magento2CategoryService::getInstance();
        
        $categories = new DataObject\Category\Listing();
        $categories->addConditionParam("export_to_magento = ?", "1");
        $categories->addConditionParam("magento_syncronized = ?", "0");
        $categories->setLimit("30");
        
        $count = 0;
        $err = 0;
        $next = $categories->count() > 0;
        while($next){
            $category = $categories->current();
            
            try{
                $categoryUtils->export($category);
                $count++;
            } catch(\Exception $e){
                Logger::err($e->getMessage());
                $err++;
            }
            
            $next = $categories->next();
        }
        
        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }
        
        $datetime = date("Y-m-d H:i:s");
        
        if($count > 0){
            Logger::debug("Sincronizzate correttamente $count categorie. $err categorie hanno causato un errore.");      
            return new Response("[$datetime] - Sincronizzate correttamente $count categorie. $err categorie hanno causato un errore.");
        }else{
            Logger::debug("Nessuna categoria sincronizzata. $err categorie hanno causato un errore.");      
            return new Response("[$datetime] - Nessuna categoria sincronizzata. $err categorie hanno causato un errore.");
        }
    }
    
    /**
     * @Route("/sync_products")
     */
    public function syncProductsAction(Request $request)
    {
        $productUtils = Magento2ProductService::getInstance();
        
        $products = new DataObject\Product\Listing();
        $products->addConditionParam("export_to_magento = ?", "1");
        $products->addConditionParam("magento_syncronized = ?", "0");
        $products->setLimit("10");
        
        $count = 0;
        $err = 0;
        $next = $products->count() > 0;
        while($next){
            $product = $products->current();

            try{
                $productUtils->export($product);
                $count++;
            } catch(\Exception $e){
                Logger::err($e->getMessage());
                $err++;
            }
            
            $next = $products->next();
        }
        
        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }
        
        $datetime = date("Y-m-d H:i:s");
        
        if($count > 0){
            Logger::debug("Sincronizzati correttamente $count prodotti. $err prodotti hanno causato un errore.");      
            return new Response("[$datetime] - Sincronizzati correttamente $count prodotti. $err prodotti hanno causato un errore.");
        }else{
            Logger::debug("Nessun prodotto sincronizzato. $err prodotti hanno causato un errore.");      
            return new Response("[$datetime] - Nessun prodotto sincronizzato. $err prodotti hanno causato un errore.");
        }
    }

}

<?php

namespace Magento2PimcoreBundle\Controller;

use Magento2PimcoreBundle\EventListener\Magento2PimcoreCategoryListener;
use Pimcore\Model\DataObject;
use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
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
        $categories->setLimit("5");
        
        $count = 0;
        $next = $categories->count() > 0;
        while($next){
            $category = $categories->current();
            
            $category->beginTransaction();
            $categoryListener->onPostUpdate($category);
            $category->commit();
            
            $count++;
            $next = $categories->next();
        }
        
        if($count > 0){
            Logger::debug("Sincronizzate correttamente $count categorie.");      
            return new Response("Sincronizzate correttamente $count categorie.");
        }else{
            Logger::debug("Nessuna categoria da sincronizzare.");      
            return new Response("Nessuna categoria da sincronizzare.");
        }
    }

}

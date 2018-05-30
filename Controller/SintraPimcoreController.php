<?php

namespace SintraPimcoreBundle\Controller;

use PHPShopify\Exception\SdkException;
use Pimcore\Analytics\Piwik\Api\Exception\ApiException;
use Pimcore\Tool\RestClient\Exception;
use SintraPimcoreBundle\ApiManager\ProductAPIManager;
use SintraPimcoreBundle\Controller\Sync\Mage2SyncController;
use SintraPimcoreBundle\Controller\Sync\ShopifySyncController;
use SintraPimcoreBundle\Services\Magento2\Magento2CategoryService;
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
        
        $categories->load();

        $response = array(
            "started" => date("Y-m-d H:i:s"),
            "finished" => "",
            "total elements" => 0,
            "syncronized elements" => 0,
            "elements with errors" => 0,
            "errors" => array()
        );

        $next = $categories->count() > 0;

        $totalElements = 0;
        $syncronizedElements = 0;
        $elementsWithError = 0;

        while($next){
            $category = $categories->current();
            
            try{
                $categoryUtils->export($category);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$category->getId().": ".$ex->getMessage();
                Logger::err($e->getMessage());

                $elementsWithError++;
            }

            $totalElements++;

            $next = $categories->next();
        }
        
        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }
        
        $datetime = date("Y-m-d H:i:s");
        
        $response["finished"] = $datetime;
        $response["total elements"] = $totalElements;
        $response["syncronized elements"] = $syncronizedElements;
        $response["elements with errors"] = $elementsWithError;

        Logger::info("CATEGORIES SYNCRONIZATION RESULT: ".print_r(['success' => $elementsWithError == 0, 'responsedata' => $response],true));
        return new Response("[$datetime] - CATEGORIES SYNCRONIZATION RESULT: ".print_r(['success' => $elementsWithError == 0, 'responsedata' => $response],true).PHP_EOL);

    }
    
    /**
     * @Route("/sync_products")
     */
    public function syncProductsAction(Request $request)
    {
        $response = [];
        try {
            // TODO: modular activate/deactivate of Ecomm sync
            // Mage2 Sync
            $response[] = (new Mage2SyncController())->syncProducts();
            // Shopify Sync
            $response[] = (new ShopifySyncController())->syncProducts();

            Cache::clearTag("output");
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
        }

        return new Response(implode('<br>'.PHP_EOL, $response));
    }
}

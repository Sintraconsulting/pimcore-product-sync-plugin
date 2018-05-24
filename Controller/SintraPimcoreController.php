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
        $response = [];
        try {
            // TODO: modular activate/deactivate of Ecomm sync
            // Mage2 Sync
//            $response[] = (new Mage2SyncController())->syncProducts();
            // Shopify Sync
            $response[] = (new ShopifySyncController())->syncProducts();

            Cache::clearTag("output");
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
        }

        return new Response(implode('<br>'.PHP_EOL, $response));
    }

    /**
     * @Route("/shopify_test")
     */
    public function testShopify (Request $request) {
        $productApi = ProductAPIManager::getInstance()->getShopifyApiInstance();
        $product =  (json_decode(file_get_contents(__DIR__ . '/../Services/config/product.json'), true))['shopify'];
        $product['title'] = 'Scimbare';
//        $product['id'] = 906063282233;
        $product['body_html'] = 'Schimbare BODY';
        $product['variants'][0]['weight'] = 2;
        $product['variants'][0]['price'] = 12.5;
        $product['metafield_global_description_tag'] = 'vrajeala, schimbat';
        $product['metafields_global_title_tag'] = 'Vrajeala schimbare';
        try {
            return new Response(json_encode($productApi->Product(905733701689)->put($product)));
        } catch (\PHPShopify\Exception\ApiException $e) {
            return new Response($e->getMessage());
        }
    }

    /**
     * @Route("/shopify_create")
     * @param Request $request
     * @return Response
     */
    public function shopifyCreate (Request $request) {
        $productApi = ProductAPIManager::getInstance()->getShopifyApiInstance();
        $product =  (json_decode(file_get_contents(__DIR__ . '/../Services/config/product.json'), true))['shopify'];
        $product['title'] = 'Scimbare';
        $product['body_html'] = 'Schimbare BODY';
        $product['variants'][0]['price'] = 12.5;
        $product['metafield_global_description_tag'] = 'vrajeala, schimbat';
        $product['metafields_global_title_tag'] = 'Vrajeala schimbare';
        return new Response(json_encode($productApi->Product->post($product)));
    }
}

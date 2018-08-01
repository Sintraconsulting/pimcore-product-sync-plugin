<?php

namespace SintraPimcoreBundle\Controller;

use SintraPimcoreBundle\Controller\Sync\BaseSyncController;
use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Cache;
use Pimcore\Controller\Controller;
use Pimcore\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SintraPimcoreBundle\Utils\GeneralUtils;

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;


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
     * Syncronize objects in all enabled servers
     * 
     * @Route("/sync_objects")
     */
    public function syncObjectsAction(Request $request)
    {
        $class = $request->get("class");
        $availableClasses = GeneralUtils::getAvailableClasses();
        
        if(!in_array($class, $availableClasses)){
            throw new \Exception("Invalid class '".$class."'. Please choose a value in ['". implode("','", $availableClasses)."']");
        }
        
        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        
        $response = [];
        try {
            
            if ($namespace) {
                $ctrName = $namespace . '\SintraPimcoreBundle\Controller\Sync\CustomBaseSyncController';
            } 

            if($ctrName != null && class_exists($ctrName)){
                $syncCTRClass =  new \ReflectionClass($ctrName);
                $syncCTR = $syncCTRClass->newInstance();
            }else {
                $syncCTR = new BaseSyncController();
            }
        
            $servers = $syncCTR->getEnabledServers();
            
            foreach ($servers as $server) {
                $response[] = ($syncCTR->syncServerObjects($server, $class));
            }

            Cache::clearTag("output");
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
            echo $e->getMessage();
        }

        return new Response(implode('<br>'.PHP_EOL, $response));
    }
}

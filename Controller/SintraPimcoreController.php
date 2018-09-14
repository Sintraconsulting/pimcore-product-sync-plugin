<?php

namespace SintraPimcoreBundle\Controller;

use SintraPimcoreBundle\Controller\Sync\BaseSyncController;
use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Cache;
use Pimcore\Controller\Controller;
use Pimcore\Model\DataObject\TargetServer;
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
        set_time_limit(480);

        $class = $request->get("class");
        $availableClasses = GeneralUtils::getAvailableClasses();

        if(!in_array($class, $availableClasses)){
            throw new \Exception("Invalid class '".$class."'. Please choose a value in ['". implode("','", $availableClasses)."']");
        }

        $customFilters = [];
        $execTime = $request->get('execTime');
        $maxSyncTime = $request->get('maxSyncTime');
        $typicalSyncTime = $request->get('typicalSyncTime');
        if ($execTime && $maxSyncTime && $typicalSyncTime) {
            $customFilters += ['execTime' => $execTime];
            $customFilters += ['maxSyncTime' => $maxSyncTime];
            $customFilters += ['typicalSyncTime' => $typicalSyncTime];
        }

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];

        $response = [];

        $semaphore = __DIR__ ."/syncronization.lock";
        if(!file_exists($semaphore)){
            $file = fopen($semaphore, "w");

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

                $servers = new TargetServer\Listing();

                if($request->get("server") != null && !empty($request->get("server"))){
                    $servers->setCondition("o_key = ?",$request->get("server"));
                }else{
                    $servers = $syncCTR->getEnabledServers();
                }

                $limit = $request->get("limit");

                foreach ($servers->getObjects() as $server) {
                    if($limit != null && !empty($limit) && (ctype_digit($limit) || is_int($limit))){
                        $response[] = ($syncCTR->syncServerObjects($server, $class, $limit, $customFilters));
                    }else{
                        $response[] = ($syncCTR->syncServerObjects($server, $class, 10, $customFilters));
                    }
                }

                Cache::clearTag("output");

                fclose($file);
                unlink($semaphore);

            } catch (\Exception $e) {
                fclose($file);
                unlink($semaphore);

                Logger::err($e->getMessage());
                echo $e->getMessage();
            }
        }

        return new Response(implode('<br>'.PHP_EOL, $response));
    }
}

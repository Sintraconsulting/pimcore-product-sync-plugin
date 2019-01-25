<?php

namespace SintraPimcoreBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Cache;
use Pimcore\Controller\Controller;
use Pimcore\Model\DataObject\TargetServer;
use Pimcore\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SintraPimcoreBundle\Utils\GeneralUtils;
use SintraPimcoreBundle\Utils\SynchronizationUtils;
use Pimcore\Db;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

/**
 * Controller for SintraPimcoreBundle
 * Provide the method to synchronize objects on external servers
 *
 * @author Sintra Consulting
 *
 * @Route("/sintra_pimcore")
 */
class SintraPimcoreController extends Controller implements AdminControllerInterface {

    /**
     * @inheritDoc
     */
    public function needsSessionDoubleAuthenticationCheck() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function needsStorageDoubleAuthenticationCheck() {
        return false;
    }

    /**
     * Synchronize objects in one or more servers
     *
     * @param Request $request the action request. It could have these arguments:
     *      - class (mandatory):  a valid Classname, to be choosen from the
     *                            Pimcore created classes.
     *
     *      - server:             if passed, the server in which object must be synchronized.
     *                            If not passed, all enabled servers will be considered.
     *
     *      - limit (default 10): the number of object to synchronize per iteration.
     *
     *      - execTime:           if passed, specify the maximum execution time (in seconds).
     *                            in combination with 'maxSyncTime' and 'typicalSyncTime'
     *                            override the limit parameter
     *
     *      - maxSyncTime:        if passed, specify the empiric maximum synchronization time for a single object (in seconds).
     *                            in combination with 'execTime' and 'typicalSyncTime'
     *                            override the limit parameter
     *
     *      - typicalSyncTime:    if passed, specify the empiric typicall synchronization time for a single object  (in seconds).
     *                            in combination with 'execTime' and 'maxSyncTime'
     *                            override the limit parameter
     *
     * @return Response The synchronization result
     * @throws \Exception
     *
     * @Route("/sync_objects")
     */
    public function syncObjectsAction(Request $request) {
        $class = $request->get("class");
        $availableClasses = GeneralUtils::getAvailableClasses();

        if (!in_array($class, $availableClasses)) {
            throw new \Exception("Invalid class '" . $class . "'. Please choose a value in ['" . implode("','", $availableClasses) . "']");
        }

        /**
         * Build the custom filter with timing from the request
         * They will be used to properly define a valid limit
         * of object to synchronize at each iteration.
         */
        $customFilters = [];
        $execTime = $request->get('execTime');
        $maxSyncTime = $request->get('maxSyncTime');
        $typicalSyncTime = $request->get('typicalSyncTime');
        if ($execTime && $maxSyncTime && $typicalSyncTime) {
            $customFilters += ['execTime' => $execTime];
            $customFilters += ['maxSyncTime' => $maxSyncTime];
            $customFilters += ['typicalSyncTime' => $typicalSyncTime];
        }
        
        $server = strtolower($request->get("server",""));

        $response = [];

        /**
         * Add a semaphore control in order to avoid concurrent synchronizations
         * Perform the object synchronization and return the results.
         *
         * If an unexpected error occours, it will be reported in the custom log table
         * in the database
         */
        $semaphore = __DIR__ . "/synchronization_".$server."_".strtolower($class).".lock";
        
        if (file_exists($semaphore) && filemtime($semaphore) < strtotime("-2 hour")) {
            unlink($semaphore);
        }
        
        if (!file_exists($semaphore)) {
            $file = fopen($semaphore, "w");

            try {
                $this->doObjectsSynchronization($request, $response, $class, $customFilters);

                Cache::clearAll();

                fclose($file);
                unlink($semaphore);
            } catch (\Error $e) {
                fclose($file);
                unlink($semaphore);

                $this->logSynchronizationError($class, $e->getMessage());
                echo $e->getMessage();
            } catch (\Exception $e) {
                fclose($file);
                unlink($semaphore);

                $this->logSynchronizationError($class, $e->getMessage());
                echo $e->getMessage();
            }
        }

        return new Response(implode('<br>' . PHP_EOL, $response));
    }

    /**
     * Perfor objects synchronization for a specific class.
     * If no specific server is requested,
     * all enabled servers will be taken into consideration.
     *
     * Set a default limit in number of objects to synchronize
     * if this value is missing or is invalid from the request.
     *
     * @param Request $request the action request
     *        GET params:
     *          - server(string) : If set, the sync will be applied for the specified server name.
     *                             Otherwise, will sync all enabled servers
     *          - limit(int) : Number of products to be synced per server. Default 10.
     * @param array $response The synchronization result
     * @param String $class the class of the objects to synchronize
     * @param array $customFilters cointains synchronization timing frpm the request.
     * @throws \Exception
     */
    private function doObjectsSynchronization(Request $request, array &$response, $class, $customFilters = []) {
        $syncCTR = SynchronizationUtils::getBaseSynchronizationController();

        $servers = new TargetServer\Listing();

        # If a specific server is targeted
        if ($request->get("server") != null && !empty($request->get("server"))) {
            $servers->setCondition("o_key = ?", $request->get("server"));
        } else {
            # Otherwise get all enabled servers
            $servers = $syncCTR->getEnabledServers();
        }

        $limit = $request->get("limit");
        if ($limit == null || empty($limit) || !ctype_digit($limit) || !is_int($limit)) {
            # If limit is not set as a parameter, default number of products synced to 10
            $limit = 10;
        }

        foreach ($servers->getObjects() as $server) {
            $response[] = ($syncCTR->syncServerObjects($server, $class, $limit, $customFilters));
        }
    }

    private function logSynchronizationError($class, $message) {
        $db = Db::get();
        $db->insert(BaseEcommerceConfig::getCustomLogTableName(), array(
            "gravity" => "HIGH",
            "class" => "SintraPimcoreController",
            "action" => "syncObjectsAction",
            "flow" => "$class Syncronization",
            "description" => $message,
            "timestamp" => time()
        ));

        Logger::err($message);
    }

}

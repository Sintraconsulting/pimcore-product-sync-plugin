<?php

namespace SintraPimcoreBundle\Controller\Sync;

use Pimcore\Cache;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Logger;
use ReflectionClass;
use Pimcore\Db;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

class BaseSyncController {

    /**
     * Get the list of enabled TargetServer
     *
     * @return \Pimcore\Model\DataObject\TargetServer\Listing
     */
    public function getEnabledServers(){
        $servers = new TargetServer\Listing();
        $servers->addConditionParam('enabled', true);

        return $servers;
    }


    /**
     * Dispatch syncronization invoking the server related syncronization service
     *
     * @param TargetServer $server
     * @param $class
     * @throws \ReflectionException
     */
    public function syncServerObjects ($server, $class, $limit = 10, $customFilters = []) {
        $serverType = $server->getServer_type();

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $ctrName = null;
        $serviceName = null;

        if ($namespace) {
            $ctrName = $namespace . '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
            $serviceName = $namespace . '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }

        $syncController = null;
        if($ctrName != null && class_exists($ctrName)){
            $syncControllerClass =  new ReflectionClass($ctrName);

            $syncController = $syncControllerClass->newInstance();
            $dataObjects = $syncController->getServerToSyncObjects($server, $class, $limit, $customFilters);
        }else {
            $dataObjects = $this->getServerToSyncObjects($server, $class, $limit);
        }

        if($serviceName == null || !class_exists($serviceName)){
            $serviceName = "\SintraPimcoreBundle\Services\\" . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }

        if($dataObjects != null && !empty($dataObjects)){
            $dataObjectServiceClass = new ReflectionClass($serviceName);
            $dataObjectService = $dataObjectServiceClass->newInstanceWithoutConstructor();
            $dataObjectService = $dataObjectService::getInstance();

            return $syncController != null ?
                    ($syncController->exportDataObjects($dataObjectService, $dataObjects, $server, $class, $customFilters)) :
                    ($this->exportDataObjects($dataObjectService, $dataObjects, $server, $class));
        }

        Logger::info("BaseSyncController - There are no $class to sync for '".$server->getServer_name()."' server");
        return "BaseSyncController - There are no $class to sync for '".$server->getServer_name()."' server";
    }

    /**
     * get a batch of ids of objects that need to be syncronized in a specific server
     *
     * @param TargetServer $server
     * @param $class
     * @param int $limit
     */
    public function getServerToSyncObjects (TargetServer $server, $class, $limit) {
        /**
         * dynamically get syncronization info tablename starting from class definition.
         * take the field collection type from the exportServers field allowed types.
         */
        $classDef = ClassDefinition::getByName($class);
        $fieldCollName = $classDef->getFieldDefinition('exportServers')->getAllowedTypes()[0];
        $classId = $classDef->getId();
        $fieldCollectionTable = 'object_collection_' . $fieldCollName . '_' .$classId;

        $db = Db::get();
        $objIds = $db->fetchAll(
            "SELECT dependencies.sourceid FROM dependencies"
            . " INNER JOIN $fieldCollectionTable as srv ON (dependencies.sourceid = srv.o_id AND srv.name=? AND srv.export = 1 AND (srv.sync = 0 OR srv.sync IS NULL))"
            . " INNER JOIN objects as obj ON (obj.o_id = dependencies.sourceid AND obj.o_className = ? AND obj.o_type = 'object')"
            . " WHERE dependencies.targetid = ? AND dependencies.targettype LIKE 'object' AND dependencies.sourcetype LIKE 'object'"
            . " ORDER BY dependencies.sourceid ASC"
            . " LIMIT $limit",
            [ $server->getKey(), $class, $server->getId() ]);

        $ids = [];
        foreach ($objIds as $id) {
            $ids[] = $id['sourceid'];
        }

        return $ids;
    }

    /**
     * @param InterfaceService  $dataObjectService
     * @param array $dataObjects
     * @param TargetServer $server
     * @return string
     */
    protected function exportDataObjects (InterfaceService $dataObjectService, $dataObjects, TargetServer $server, $class, $customFilters = []) {
        $response = array(
                "started" => date("Y-m-d H:i:s"),
                "finished" => "",
                "total elements" => 0,
                "syncronized elements" => 0,
                "elements with errors" => 0,
                "errors" => array()
        );

        $totalElements = 0;
        $syncronizedElements = 0;
        $elementsWithError = 0;
        $startTime = $this->millitime();

        foreach ($dataObjects as $productId) {

            try{
                $dataObjectService->export($productId, $server);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$productId.": ".$e->getMessage();

                $db = Db::get();
                $db->insert(BaseEcommerceConfig::getCustomLogTableName(), array(
                    "gravity" => "LOW",
                    "class" => "BaseSyncController",
                    "action" => "exportDataObjects",
                    "flow" => "Sync to ".$server->getServer_name(),
                    "description" => "ERROR in exporting object with Id '$productId': ".$e->getMessage(),
                    "timestamp" => time()
                ));

                Logger::err($e->getMessage());
                Logger::err($e->getTraceAsString());

                $elementsWithError++;
            }

            $totalElements++;

        }
        $endTime = $this->millitime();
        try{
            Cache::clearAll();
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }

        $response["total elements"] = $totalElements;
        $response["syncronized elements"] = $syncronizedElements;
        $response["elements with errors"] = $elementsWithError;

        return $this->logSyncedProducts($response, $server->getServer_name(), $class, $endTime-$startTime, null);
    }

    protected function logSyncedProducts ($response, $ecomm, $class, $duration, $finished = null) {
        if (!$finished) {
            $finished = date("Y-m-d H:i:s");
        }
        $response["finished"] = $finished;

        $syncLogFile = fopen(PIMCORE_LOG_DIRECTORY . "/syncObjects.log", "a") or die("Unable to open file!");
        $syncLog = "[" . Date("Y-m-d H:i:s") . "] - ".strtoupper($class)." $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response, 'duration' => $duration . ' ms'],true);
        fwrite($syncLogFile, $syncLog . PHP_EOL);
        fclose($syncLogFile);

        return ("[$finished] - ".strtoupper($class)." $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response , 'duration' => $duration . ' ms'],true).PHP_EOL);
    }

    protected function millitime() {
        $microtime = microtime();
        $comps = explode(' ', $microtime);

        // Note: Using a string here to prevent loss of precision
        // in case of "overflow" (PHP converts it to a double)
        return sprintf('%d%03d', $comps[1], $comps[0] * 1000);
    }
}

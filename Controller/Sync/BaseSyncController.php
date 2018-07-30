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
     * Dispatch syncronization invoking the server related syncronization service
     * 
     * @param TargetServer $server
     * @param $class
     * @throws \ReflectionException
     */
    public function syncServerObjects ($server, $class) {
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
            $dataObjects = $syncController->getServerToSyncObjects($server, $class);
        }else {
            $dataObjects = $this->getServerToSyncObjects($server, $class);
        }
        
        if($serviceName == null || !class_exists($serviceName)){
            $serviceName = "\SintraPimcoreBundle\Services\\" . ucfirst($serverType) . '\\' . ucfirst($serverType) . ucfirst($class) . 'Service';
        }

        if($dataObjects != null && !empty($dataObjects)){
            $dataObjectServiceClass = new ReflectionClass($serviceName);
            $dataObjectService = $dataObjectServiceClass->newInstanceWithoutConstructor();
            $dataObjectService = $dataObjectService::getInstance();
            
            return $syncController != null ?
                    ($syncController->exportDataObjects($dataObjectService, $dataObjects, $server, $class)) :
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
    public function getServerToSyncObjects (TargetServer $server, $class, $limit = 10) {
        /**
         * dynamically get syncronization info tablename starting from class definition.
         * take the field collection type from the exportServers field allowed types.
         */
        $classDef = ClassDefinition::getByName($class);
        $fieldCollName = $classDef->getFieldDefinition('exportServers')->getAllowedTypes()[0];
        $classId = $classDef->getId();
        $objectTableClass = 'object_query_' . $classId;
        $fieldCollectionTable = 'object_collection_' . $fieldCollName . '_' .$classId;
        
        $db = Db::get();
        $objIds = $db->fetchAll(
            "SELECT dependencies.sourceid FROM dependencies"
            . " INNER JOIN $fieldCollectionTable as srv ON (dependencies.sourceid = srv.o_id AND srv.name=? AND srv.export = 1 AND (srv.sync = 0 OR srv.sync IS NULL))"
            . " INNER JOIN $objectTableClass as prod ON (prod.oo_id = dependencies.sourceid AND prod.oo_className = ? )"
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
    protected function exportDataObjects (InterfaceService $dataObjectService, $dataObjects, TargetServer $server, $class) {
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

        foreach ($dataObjects as $productId) {
            
            try{
                $dataObjectService->export($productId, $server);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$productId.": ".$e->getMessage();
                Logger::err($e->getMessage());
                Logger::err($e->getTraceAsString());

                $elementsWithError++;
            }

            $totalElements++;

        }

        try{
            Cache::clearTag("output");
        } catch(\Exception $e){
            Logger::err($e->getMessage());
        }

        $response["total elements"] = $totalElements;
        $response["syncronized elements"] = $syncronizedElements;
        $response["elements with errors"] = $elementsWithError;

        return $this->logSyncedProducts($response, $server->getServer_name(), $class);
    }

    protected function logSyncedProducts ($response, $ecomm, $class, $finished = null) {
        if (!$finished) {
            $finished = date("Y-m-d H:i:s");
        }
        $response["finished"] = $finished;

        Logger::info(strtoupper($class)." $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true));
        return ("[$finished] - ".strtoupper($class)." $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true).PHP_EOL);
    }
}
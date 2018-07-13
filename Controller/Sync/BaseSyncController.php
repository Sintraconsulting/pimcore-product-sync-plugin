<?php

namespace SintraPimcoreBundle\Controller\Sync;

use Pimcore\Cache;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Product;
use Pimcore\Model\DataObject\Product\Listing;
use Pimcore\Model\DataObject\TargetServer;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Logger;
use ReflectionClass;
use Pimcore\Db;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

class BaseSyncController {
    protected $ecommerce;

    /**
     * Dispatch syncronization invoking the server related syncronization service
     * 
     * @param TargetServer $server
     * @throws \ReflectionException
     */
    public function syncServerProducts ($server) {
        $this->ecommerce = $server->getServer_name();
        $serverType = $server->getServer_type();

        $customizationInfo = BaseEcommerceConfig::getCustomizationInfo();
        $namespace = $customizationInfo["namespace"];
        $ctrName = null;
        $serviceName = null;
        
        if ($namespace) {
            $ctrName = $namespace . '\SintraPimcoreBundle\Controller\Sync\\' . ucfirst($serverType) . 'SyncController';
            $serviceName = $namespace . '\SintraPimcoreBundle\Services\\' . ucfirst($serverType) . '\\' . ucfirst($serverType) . 'ProductService';
        } 
        
        $syncController = null;
        if($ctrName != null && class_exists($ctrName)){
            $syncControllerClass =  new ReflectionClass($ctrName);
            
            $syncController = $syncControllerClass->newInstance();
            $products = $syncController->getServerToSyncProducts($server);
        }else {
            $products = $this->getServerToSyncProducts($server);
        }
        
        if($serviceName == null || !class_exists($serviceName)){
            $serviceName = "\SintraPimcoreBundle\Services\\" . ucfirst($serverType) . '\\' . ucfirst($serverType) . 'ProductService';
        }

        if($products != null && !empty($products)){
            $productServiceClass = new ReflectionClass($serviceName);
            $productService = $productServiceClass->newInstanceWithoutConstructor();
            $productService = $productService::getInstance();
            
            return $syncController != null ?
                    ($syncController->exportProducts($productService, $products, $server)) :
                    ($this->exportProducts($productService, $products, $server));
        }
        
        Logger::info("BaseSyncController - There are no product to sync for '".$server->getServer_name()."' server");
        return "BaseSyncController - There are no product to sync for '".$server->getServer_name()."' server";
    }

    /**
     * get a batch of ids of products that need to be syncronized in a specific server
     * 
     * @param TargetServer $server
     * @param int $limit
     */
    public function getServerToSyncProducts (TargetServer $server, $limit = 10) {
        /**
         * dynamically get syncronization info tablename starting from class definition.
         * take the field collection type from the exportServers field allowed types.
         */
        $classDef = ClassDefinition::getByName("Product");
        $fieldCollName = $classDef->getFieldDefinition('exportServers')->getAllowedTypes()[0];
        $classId = $classDef->getId();
        $productTableClass = 'object_query_' . $classId;
        $fieldCollectionTable = 'object_collection_' . $fieldCollName . '_' .$classId;
        
        $db = Db::get();
        $prodIds = $db->fetchAll(
            "SELECT dependencies.sourceid FROM dependencies"
            . " INNER JOIN $fieldCollectionTable as srv ON (dependencies.sourceid = srv.o_id AND srv.name=? AND srv.export = 1 AND (srv.sync = 0 OR srv.sync IS NULL))"
            . " INNER JOIN $productTableClass as prod ON (prod.oo_id = dependencies.sourceid AND prod.oo_className = 'Product' )"
            . " WHERE dependencies.targetid = ? AND dependencies.targettype LIKE 'object' AND dependencies.sourcetype LIKE 'object'"
            . " ORDER BY dependencies.sourceid ASC"
            . " LIMIT $limit",
            [ $server->getKey(), $server->getId() ]);
        
        $ids = [];
        foreach ($prodIds as $id) {
            $ids[] = $id['sourceid'];
        }

        return $ids;
    }

    protected function getEcommerce() : string {
        return $this->ecommerce;
    }

    /**
     * @param InterfaceService  $productService
     * @param array $products
     * @param TargetServer $server
     * @return string
     */
    protected function exportProducts (InterfaceService $productService, $products, TargetServer $server) {
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

        foreach ($products as $productId) {
            
            try{
                $productService->export($productId, $server);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$productId.": ".$e->getMessage();
                Logger::err($e->getMessage());

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

        return $this->logSyncedProducts($response, $this->getEcommerce());
    }

    protected function getRelationProductsFromBase ($field, $value) {
        try {
            $productsListing = new Product\Listing();
            $productsListing->setCondition("$field = ?", [$value]);
            return $productsListing;
        } catch (\Exception $e) {
            Logger::critical($e->getMessage());
        }
    }

    protected function logSyncedProducts ($response, $ecomm, $finished = null) {
        if (!$finished) {
            $finished = date("Y-m-d H:i:s");
        }
        $response["finished"] = $finished;

        Logger::info("PRODUCT $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true));
        return ("[$finished] - PRODUCT $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true).PHP_EOL);
    }
}
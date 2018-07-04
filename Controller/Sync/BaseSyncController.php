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
        $serviceName = "\SintraPimcoreBundle\Services\\" . ucfirst($serverType) . '\\' . ucfirst($serverType) . 'ProductService';

        $products = $this->getServerToSyncProducts($server);

        $productServiceClass = new ReflectionClass($serviceName);
        $productService = $productServiceClass->newInstanceWithoutConstructor();
        $productService = $productService::getInstance();

        $productsListing = new Product\Listing();
        $productsListing->setCondition("o_id IN (". implode(",", [$products]).")");

        return $this->exportProducts($productService, $productsListing, $server);
    }

    /**
     * get a batch of products that need to be syncronized in a specific server
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
        $fieldCollectionTable = 'object_collection_' . $fieldCollName . '_' .$classId;
        
        $db = Db::get();
        $prodIds = $db->fetchAll(
            "SELECT dependencies.sourceid FROM dependencies"
            . " INNER JOIN $fieldCollectionTable as srv ON (dependencies.sourceid = srv.o_id AND srv.export = 1 AND (srv.sync = 0 OR srv.sync IS NULL))"
            . " WHERE dependencies.targetid = ? AND dependencies.targettype LIKE 'object' AND dependencies.sourcetype LIKE 'object'"
            . " GROUP BY dependencies.sourceid"
            . " LIMIT $limit",
            [ $server->getId() ]);
        
        $ids = [];
        foreach ($prodIds as $id) {
            $ids[] = $id['sourceid'];
        }
        $ids = implode(', ', $ids);

        return $ids;
    }

    protected function getEcommerce() : string {
        return $this->ecommerce;
    }

    /**
     * @param InterfaceService  $productService
     * @param Listing $products
     * @param TargetServer $server
     * @return string
     */
    protected function exportProducts (InterfaceService $productService, Listing $products, TargetServer $server) {
        $response = array(
                "started" => date("Y-m-d H:i:s"),
                "finished" => "",
                "total elements" => 0,
                "syncronized elements" => 0,
                "elements with errors" => 0,
                "errors" => array()
        );
        $next = $products->count() > 0;

        $totalElements = 0;
        $syncronizedElements = 0;
        $elementsWithError = 0;

        while($next){
            $product = $products->current();

            try{
                $productService->export($product, $server);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$product->getId().": ".$e->getMessage();
                Logger::err($e->getMessage());

                $elementsWithError++;
            }

            $totalElements++;

            $next = $products->next();
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

    protected function logSyncedProducts ($response, $ecomm, $finished = null) {
        if (!$finished) {
            $finished = date("Y-m-d H:i:s");
        }
        $response["finished"] = $finished;

        Logger::info("PRODUCT $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true));
        return ("[$finished] - PRODUCT $ecomm SYNCRONIZATION RESULT: ".print_r(['success' => $response['elements with errors'] == 0, 'responsedata' => $response],true).PHP_EOL);
    }
}
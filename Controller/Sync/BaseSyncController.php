<?php

namespace SintraPimcoreBundle\Controller\Sync;

use Pimcore\Cache;
use Pimcore\Model\DataObject\Product\Listing;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Logger;

abstract class BaseSyncController {
    protected $ecommerce;

    abstract public function syncProducts(int $count = 10) : string;

    protected function getEcommerce() : string {
        return $this->ecommerce;
    }

    /**
     * @param InterfaceService  $productService
     * @param Listing $products
     * @return string
     */
    protected function exportProducts (InterfaceService $productService, Listing $products) {
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
                $productService->export($product);
                $syncronizedElements++;
            } catch(\Exception $e){
                $response["errors"][] = "OBJECT ID ".$product->getId().": ".$ex->getMessage();
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
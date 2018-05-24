<?php

namespace SintraPimcoreBundle\Controller\Sync;

use Pimcore\Model\DataObject\Product\Listing;
use SintraPimcoreBundle\Services\InterfaceService;
use Pimcore\Logger;

abstract class BaseSyncController {
    protected $ecommerce;

    abstract public function syncProducts(int $count = 10) : string;

    protected function getEcommerce() : string {
        return $this->ecommerce;
    }

    protected function exportProducts (InterfaceService $productService, Listing $products) {
        $count = 0;
        $err = 0;
        $next = $products->count() > 0;
        while($next){
            $product = $products->current();

            try{
                $productService->export($product);
                $count++;
            } catch(\Exception $e){
                Logger::err('EXPORT ERR' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
                $err++;
            }

            $next = $products->next();
        }
        return $this->logSyncedProducts($count, $err, $this->getEcommerce());
    }

    protected function logSyncedProducts ($count, $err, $ecomm, $datetime = null) {
        if (!$datetime) {
            $datetime = date("Y-m-d H:i:s");
        }
        if($count > 0){
            Logger::debug("Sincronizzati correttamente $count $ecomm prodotti. $err prodotti hanno causato un errore.");
            return ("[$datetime] - Sincronizzati correttamente $count prodotti. $err prodotti hanno causato un errore.");
        }else{
            Logger::debug("Nessun prodotto sincronizzato. $err prodotti hanno causato un errore.");
            return ("[$datetime] - Nessun prodotto sincronizzato. $err prodotti hanno causato un errore.");
        }
    }
}
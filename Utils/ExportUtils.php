<?php

namespace SintraPimcoreBundle\Utils;

use Pimcore\Model\DataObject\Product;
/**
 * Export utils
 *
 * @author Sintra Consulting
 */
class ExportUtils {

    public static function exportProduct(&$response, Product $product){
        $productExport = array(
            "id" => $product->getId(),
            "created at" => date("Y-m-d H:i:s", $product->getCreationDate()),
            "modified at" => date("Y-m-d H:i:s", $product->getModificationDate())
        );
        
        $response[] = $productExport;
    }
}

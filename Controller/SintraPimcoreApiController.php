<?php

namespace SintraPimcoreBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminControllerInterface;
use Pimcore\Controller\Controller;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Product;
use Pimcore\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;
use SintraPimcoreBundle\Utils\ExportUtils;

/**
 * Controller for SintraPimcoreApiController
 * Provide the method to export objects definition
 *
 * @author Sintra Consulting
 *
 * @Route("/sintra_pimcore/api")
 */
class SintraPimcoreApiController extends Controller implements AdminControllerInterface {

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
     * Export objects definition
     * 
     * @return Response The objects definition
     * @throws \Exception
     *
     * @Route("/export")
     */
    public function export(Request $request) {
        $response = array();
        
        $timestamp = $request->get("timestamp");
        
        $products = new Product\Listing();
        $products->setObjectTypes(array(AbstractObject::OBJECT_TYPE_OBJECT));
        
        if($timestamp != null && !empty($timestamp) && (is_numeric($timestamp) && (int)$timestamp == $timestamp)){
            $products->setCondition("o_modificationDate >= ?",$timestamp);
        }
        
        foreach ($products->getObjects() as $product) {
            ExportUtils::exportProduct($response["products"], $product);
        }

        return new Response(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

}

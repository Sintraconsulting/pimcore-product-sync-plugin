<?php

namespace SintraPimcoreBundle\Controller;

use Pimcore\Controller\FrontendController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FrontendController
{
    /**
     * @Route("/magento2_pimcore")
     */
    public function indexAction(Request $request)
    {
        return new Response('Hello world from sintra_pimcore');
    }
}

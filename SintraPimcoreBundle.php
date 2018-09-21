<?php

namespace SintraPimcoreBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use SintraPimcoreBundle\Installer\SintraPimcoreBundleInstaller;

class SintraPimcoreBundle extends AbstractPimcoreBundle
{
    public function getInstaller()
    {
        return $this->container->get(SintraPimcoreBundleInstaller::class);
    }
    
    public function getJsPaths()
    {
        return [
            '/bundles/sintrapimcore/js/pimcore/startup.js'
        ];
    }
}

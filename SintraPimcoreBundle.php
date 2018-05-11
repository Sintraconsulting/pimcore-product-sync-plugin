<?php

namespace SintraPimcoreBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class SintraPimcoreBundle extends AbstractPimcoreBundle
{
    public function getJsPaths()
    {
        return [
            '/bundles/sintrapimcore/js/pimcore/startup.js'
        ];
    }
}

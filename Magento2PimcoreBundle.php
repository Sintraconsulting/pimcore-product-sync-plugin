<?php

namespace Magento2PimcoreBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class Magento2PimcoreBundle extends AbstractPimcoreBundle
{
    public function getJsPaths()
    {
        return [
            '/bundles/magento2pimcore/js/pimcore/startup.js'
        ];
    }
}

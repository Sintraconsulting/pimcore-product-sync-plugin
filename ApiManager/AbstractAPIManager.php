<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

/**
 * Abstract API Manager 
 *
 * @author Marco Guiducci
 */
abstract class AbstractAPIManager {
    
    protected static abstract function getApiInstance(TargetServer $server);

}

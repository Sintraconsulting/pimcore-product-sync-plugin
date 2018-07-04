<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

/**
 * Magento Rest API Manager 
 *
 * @author Marco Guiducci
 */
abstract class AbstractAPIManager {

    protected static $instance;

    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    public abstract function getApiInstance(TargetServer $server);

}

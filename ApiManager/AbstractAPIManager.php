<?php

namespace SintraPimcoreBundle\ApiManager;

use Pimcore\Model\DataObject\TargetServer;

/**
 * Abstract API Manager Class 
 * Must be extended for each kind of E-Commerce integration
 *
 * @author Sintra Consulting
 */
abstract class AbstractAPIManager {
    
    /**
     * Abstract method to get API Client Instance
     * 
     * @param TargetServer $server
     */
    protected static abstract function getApiInstance(TargetServer $server);

}

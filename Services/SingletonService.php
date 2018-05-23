<?php
namespace SintraPimcoreBundle\Services;

class SingletonService {

    protected static $instance;

    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
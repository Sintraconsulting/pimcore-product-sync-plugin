<?php

class SingletonService {

    private static $instance;

    public static function getInstance() {
        if (is_null(self::$instance)) {
            static::$instance = new self();
        }
        return static::$instance;
    }
}
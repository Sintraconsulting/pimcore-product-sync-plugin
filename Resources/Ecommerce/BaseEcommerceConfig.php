<?php
namespace SintraPimcoreBundle\Resources\Ecommerce;

class BaseEcommerceConfig {
    public static $updateProductPrices = false;
    protected static $url;
    protected static $apiKey;
    protected static $pimcoreBaseUrl = "http://your/pimcore/url";

    public static function getConfig() {
        return array(
                "path" => static::$url,
                "apiKey" => static::$apiKey
        );
    }

    public static function getBaseUrl(){
        return static::$pimcoreBaseUrl;
    }
}
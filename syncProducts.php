<?php

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

include_once 'Resources/Ecommerce/BaseEcommerceConfig.php';

$baseUrl = BaseEcommerceConfig::getBaseUrl();

$url = $baseUrl.'/sintra_pimcore/sync_objects?class=Product';

$options = getopt(null, ["server:","limit:"]);

if(array_key_exists("server", $options)){
    $url .= "&server=".$options["server"];
}

if(array_key_exists("limit", $options)){
    $url .= "&limit=".$options["limit"];
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);

$result = curl_exec($curl);

curl_close($curl);


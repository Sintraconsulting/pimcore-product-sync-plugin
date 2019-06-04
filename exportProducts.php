<?php

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

include_once 'Resources/Ecommerce/BaseEcommerceConfig.php';

$baseUrl = BaseEcommerceConfig::getBaseUrl();

$url = $baseUrl.'/sintra_pimcore/api/export';

$options = getopt(null, ["timestamp:","exportAll:","offset:","limit:","writeInFile:"]);

if(array_key_exists("exportAll", $options)){
    $url .= "?exportAll=".$options["exportAll"];
}else{
    $url .= "?exportAll=0";
}

if(array_key_exists("timestamp", $options)){
    $url .= "&timestamp=".$options["timestamp"];
}

if(array_key_exists("offset", $options)){
    $url .= "&offset=".$options["offset"];
}

if(array_key_exists("limit", $options)){
    $url .= "&limit=".$options["limit"];
}

if(array_key_exists("writeInFile", $options)){
    $url .= "&writeInFile=".$options["writeInFile"];
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($curl);

curl_close($curl);

echo $result;

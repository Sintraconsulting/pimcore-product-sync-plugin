<?php

use SintraPimcoreBundle\Resources\Ecommerce\BaseEcommerceConfig;

include_once 'Resources/Ecommerce/BaseEcommerceConfig.php';

$baseUrl = BaseEcommerceConfig::getBaseUrl();

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl.'/sintra_pimcore/sync_objects?class=product');

$result = curl_exec($curl);

curl_close($curl);


<?php

use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;

include_once 'Resources/Ecommerce/MagentoConfig.php';

$baseUrl = MagentoConfig::getBaseUrl();

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl.'/sintra_pimcore/sync_objects?class=category');

$result = curl_exec($curl);

curl_close($curl);


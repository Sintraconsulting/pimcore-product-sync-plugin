<?php

use SintraPimcoreBundle\Resources\Ecommerce\MagentoConfig;
use Pimcore\Logger;

include_once 'Resources/Magento/MagentoConfig.php';

$baseUrl = MagentoConfig::getBaseUrl();

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl.'/sintra_pimcore/sync_categories');

$result = curl_exec($curl);

curl_close($curl);


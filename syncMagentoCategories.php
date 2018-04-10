<?php

use Magento2PimcoreBundle\Resources\Magento\MagentoConfig;
use Pimcore\Logger;

include_once 'Resources/Magento/MagentoConfig.php';

$baseUrl = MagentoConfig::getBaseUrl();

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $baseUrl.'/magento2_pimcore/sync_magento_categories');

$result = curl_exec($curl);

curl_close($curl);


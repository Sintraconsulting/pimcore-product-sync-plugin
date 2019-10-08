<?php

/*
 * Replace with your pimcore url
 */
$baseUrl = "http://pimcore-6.local:9090";

$url = $baseUrl.'/sintra_pimcore/sync_objects?class=Product';

$options = getopt(null, ["server:","limit:","execTime:","maxSyncTime:","typicalSyncTime:"]);

if(array_key_exists("server", $options)){
    $url .= "&server=".$options["server"];
}

if(array_key_exists("limit", $options)){
    $url .= "&limit=".$options["limit"];
}

if(array_key_exists("execTime", $options)){
    $url .= "&execTime=".$options["execTime"];
}

if(array_key_exists("maxSyncTime", $options)){
    $url .= "&maxSyncTime=".$options["maxSyncTime"];
}

if(array_key_exists("typicalSyncTime", $options)){
    $url .= "&typicalSyncTime=".$options["typicalSyncTime"];
}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);

$result = curl_exec($curl);

curl_close($curl);


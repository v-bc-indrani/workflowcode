<?php

$url= 'https://store-bzynwrh.mybigcommerce.com/api/v2/products.json?limit=200&page=1';
//$apikey = '&apikey=864de9f6f832994db4d5ff29046a9c5595066ac3';

//echo "host string " .$url."\n";
$username='admin';
$password='864de9f6f832994db4d5ff29046a9c5595066ac3';

$ch=curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$response = json_decode(curl_exec($ch));

//$information=curl_getinfo($process);
//print_r($return);
foreach ($response as $key => $value) {
    echo "id is ".print_r ($value->id)."\n";
    echo " name is ".print_r($value->name)."\n";
    
}
//curl_close($process);

?>




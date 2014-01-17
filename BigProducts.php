<?php

$url= 'https://store-bzynwrh.mybigcommerce.com/api/v2/products.json?limit=200&page=1';
//$apikey = '&apikey=864de9f6f832994db4d5ff29046a9c5595066ac3';

$username='admin';
$password='864de9f6f832994db4d5ff29046a9c5595066ac3';

$ch=curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$response = json_decode(curl_exec($ch));
//print_r($response);

 echo "<table id='display' align = 'center' border='1'>";
 echo "<tr align='center'><td colspan ='3'> <b>Product Details</b></td></tr>";
 echo "<tr> " ;
 echo "<td><b> Prod ID </b>  </td>".    "<td> <b>ProductName</b> </td> ".    " <td><b> Price </b>  </td>";
 echo "</tr> " ;
 foreach ($response as $key => $value) {
	$exp1= explode(']',$value->name,7);
	echo "<tr> " ;
	echo "<td> $value->id </td>" ."<td>  $exp1[1]</td>" . "<td> $value->price</td> ";
      echo "</tr>";
	}

 echo "</table>";
 curl_close($ch);
?>










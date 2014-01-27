<?php
   

$url= 'http://api.nzpost.co.nz/ratefinder/rate.json?api_key=e3bed3c0-cad5-012e-0668-000c29b44ac0&height=100&length=100&service_group_description=Standard&postcode_dest=6011&postcode_src=6012&thickness=100&weight=1';

http://api.nzpost.co.nz/ratefinder/rate.json?api_key=123&height=100&length=100&postcode_dest=6011&postcode_src=6012&thickness=100&weight=1
//$apikey = '&apikey=864de9f6f832994db4d5ff29046a9c5595066ac3';

//$username='admin';
//$password='864de9f6f832994db4d5ff29046a9c5595066ac3';

$ch=curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$send_request= curl_setopt($ch,CURLINFO_HEADER_OUT);
var_dump($send_request);

$response = json_decode(curl_exec($ch));

print_r($response);

/* echo "<table id='display' align = 'center' border='1'>";
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

 echo "</table>";*/
 
 curl_close($ch);
?>










?>
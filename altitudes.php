
<?php

class altitudes
{
	   public $altitude1;
	   public $altitude; 
	      
      public function __construct($address){
		              
       // $address='Alameda Place Rolleston new zealand';
        $prepAddr = str_replace(' ','+',$address);
        $geocode=file_get_contents('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
        $output= json_decode($geocode);
	  $latitude = $output->results[0]->geometry->location->lat;
     	  $longitude = $output->results[0]->geometry->location->lng;
        $this->altitude1 = $latitude;
        $this->altitude = $longitude;
	  echo " in the success". $altitude;
	}
 }
$address1='Alameda Place Rolleston new zealand';
$addressDetails= new altitudes($address1);
echo "calling";
echo  "alti1 ". $addressDetails->altitude ."\n";
echo "alti2 ". $addressDetails->altitude1 ."\n";
?>
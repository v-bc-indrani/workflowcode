<?php
//include('altitudes.php');
class Shipping_Provider_Newzealand_Service extends Shipping_Service
{
    const SUPPORTED_CURRENCY_CODE = "NZD";
        const ORIGIN_COUNTRY_CODE = "NZ";
    //const QUOTE_URL= "http://api.nzpost.co.nz/ratefinder/domestic/rating/v2";
     const QUOTE_URL ="http://api.nzpost.co.nz/ratefinder/rate.json";
    const API_KEY = 'e3bed3c0-cad5-012e-0668-000c29b44ac0';
    //'e3bed3c0-cad5-012e-0668-000c29b44ac0';
    protected $service_codes = array( 
        'domestic_3day' => 'Nationwide 2 - 3 working days',
        'domestic_2day' => 'Nationwide Next working day',
        'domestic_next_day' => 'Nationwide 1 - 2 working days'
      );
      protected $packaging= array
         ('cylinder',
         'medium_box',
        'large_box');   
   
    /* Delivery service codes
     * */
	public function getQuote(Shipping_RateRequest $request)
	{
	
	   $options = $request->getOptions();
      
       if ($request->getCurrency() != self::SUPPORTED_CURRENCY_CODE) {
            throw new Shipping_Error_UnsupportedCurrency(array(
                'valid_currency' => self::SUPPORTED_CURRENCY_CODE,
            ));
       }
       if ($request->getOrigin()->getCountryCode() != self::ORIGIN_COUNTRY_CODE) {
            throw new Shipping_Error_InvalidOriginCountry(array(
                'valid_origin' => self::ORIGIN_COUNTRY_CODE,
            ));
       }
	 
       $requestData = $this->quoteRequest($request);
	 
      
       $responseData = $this->performQuoteRequest($requestData, $options);
	 
	 var_dump($responseData);
	 exit;
      // $responseData = $this->performPostRequest(self::QUOTE_URL, $requestData);
    }
       
       
   protected function performQuoteRequest($requestData, $options)
    {
        //Setup http client  
                           
	$headers =header('Content-Type:application/json');
	//'Accept' => 'application/api.nzpost.co.nz/ratefinder/domestic+json');
	
	echo " in the perform request";
        $this->setupHttpClient($headers);
	print_r($headers);
        $this->httpClient->setBasicAuth($options->api_key);
	  
		var_dump($this->httpClient->setBasicAuth($options->api_key));
	
		try{
           $url = self::QUOTE_URL;
	     
           $this->httpClient->post($url, $requestData);

        } catch (Interspire_Http_NetworkError $e) {
            $this->handleConnectionError($e);
        }

        $this->logHttpClientStats();
	  echo"clinet status ". var_dump($this->logHttpClientStats());
	var_dump($this->httpClient->getBody());

        return $this->httpClient->getBody();
    }
    
	 
	  
    public function quoteRequest(Shipping_RateRequest $request){
        	
       // echo " in the quote request";
        $options = $request->getOptions();
        $origin = $request->getOrigin();
        
        $destination = $request->getDestination();
	 // $this->_buildServiceFromDeliveryServices($deliveryServices);
	  $deliveryServices = $request->getDeliveryServices();
	 //print_r($deliveryServices);
	 

	  
        $addressOrg= $request->getOrigin()->getPostcode().' '.$request->getOrigin()->getCity().' '.$request->getOrigin()->getState().' '.$request->getOrigin()->getCountry();
       // echo $addressOrg; 
        
        $addressDetails= new altitudes($addressOrg);
	 // echo " after add dtails";
   	//var_dump( $addressDetails);
	//exit;
        $sourcex=$addressDetails->latitude;
        $sourcey=$addressDetails->longitude ;
        //echo " latitude is ". $sourcex."\n";
        //echo " longitude is ". $sourcey."\n";

        $destination = $request->getDestination();
        
        //print_r($destination);
        
        $addressDestination= $request->getDestination()->getPostcode().' '.$request->getDestination()->getCity().' '.$request->getDestination()->getState().' '.$request->getDestination()->getCountry();
        //print_r($addressDestination) ."\n";
        $addressDestinationDetails= new altitudes($addressDestination);
        $destiNationx=$addressDestinationDetails->latitude;
        $destiNationy=$addressDestinationDetails->longitude;
        //echo " latitude is ".$destiNationx."\n";
        //echo " longitude is ".$destiNationy."\n";
       // echo " before the parameters";
        
       //$dimensions = $this->buildDimensionvaluesFromPackages($request->getPackages(), $options);
       $packages = $request->getPackages();
       $quantity= $packages[0]->getQuantity();
       $weight=$packages[0]->getWeight()->grams();
       $length =$packages[0]->getLength()->millimeters();
       $width = $packages[0]->getWidth()->millimeters();
       $height = $packages[0]->getheight()->millimeters();
       //$value = $packages[0]->getDeclaredValue();
      // echo " before request parameters";
        $requestArrayparams = array('api_key'=> $options->apikey,
                                'length' => $length,
                                'width' => $width ,
                                'height' => $height,
                                "diameter"=>'',
                               // 'quantity' => $quantity,
                                'weight' => $weight,
                                'value'=>$options->value,
                                'source_x'=> $sourcex,
                                'source_y'=> $sourcey,
                                'dest_x'=>$destiNationx,
                                'dest_y'=>$destiNationy, 
                                'source_postcode'=>$request->getOrigin()->getPostcode(),
                                'dest_postcode'=>$request->getDestination()->getPostcode(),
                                'postage_type'=>'',
                                'rural'=>'',
                                'callback'=>'', 
                                'format'=>'json',
                                'commit' =>'Submit' ) ;
                                
       
        return array('jsonrequest' => json_encode($requestArrayparams),
        );
              
    }
    private function buildServiceFromDeliveryServices($deliveryServices)
    {
        $index = 1;

        $requestServiceCodes  = array();

        foreach ($deliveryServices as $deliveryService) {
            $requestServiceCodes[$index] =  array("service-code"=> $this->_mapServiceCode($deliveryService));

            $index++;
        }
        return $requestServiceCodes;
	  
	// print_r($requestServiceCodes);
	// exit;
    }
          
   private function mapServiceCode($deliveryService)
    {
        if (array_key_exists($deliveryService, $this->_service_codes)) {
            return $this->_service_codes[$deliveryService];
        } else {
            throw new Shipping_Error_UnsupportedOption(array('delivery_services' => Interspire_Language::translate('Shipping_Error_InvalidDeliveryService_Message', array('delivery_services' => $deliveryService))));
        }
    }
                
    
  private function setupHttpClient($headers=array(), $timeOut = 60)
    {
        if (!$this->httpClient instanceof Interspire_Http_Client) 
        {  $this->httpClient = new Interspire_Http_Client();

            $this->httpClient->setVerifyPeer();

            $this->httpClient->setTimeout($timeOut);

            $this->httpClient->failOnError();

            foreach ($headers  as $headerKey => $headerValue) {
                $this->httpClient->setHeader($headerKey, $headerValue);
            } 
        }
    }
 }

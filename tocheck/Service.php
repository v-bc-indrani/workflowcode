<?php

class Shipping_Provider_Endicia_Service extends Shipping_Service
{
    
    const SUPPORTED_CURRENCY_CODE = "USD";

    /**
     * Transform the response XML into an easier format.
     *
     * @param string $responseData Response data for the request
     * 
     * @return response object
     * 
     */
    private function _processResponse($responseData)
    {
    	  $response = Interspire_Xml::decode($responseData);

        if (isset($response['error'])) {
            $this->_handleErrorResponse($response);
        }

        return $response;
    }
    
    // added by me--- for putting into Array
     private function _processAccountResponse($responseData)
     {
     	
     	 $response = Interspire_Xml::string2Array($responseData);
    
        if (isset($response['error'])) {
            $this->_handleErrorResponse($response);
        }

        return $response;
     }
    
    // me----
    /**
     * create http client request
     *
     * @param array $headers http request headers
     * @param int   $timeOut Value
     * 
     * @return None
     */
    private function _setupHttpClient($headers = array(), $timeOut = 60)
    {
    	
        if (!$this->httpClient instanceof Interspire_Http_Client) {

            $this->httpClient = new Interspire_Http_Client();
			
            $this->httpClient->setVerifyPeer();
			
            $this->httpClient->setTimeout($timeOut);

            $this->httpClient->failOnError();
		
            foreach ($headers  as $headerKey => $headerValue) {
                $this->httpClient->setHeader($headerKey, $headerValue);
            } 
		
        }
    }
    
    
    /**
     * Request for quotes
     * 
     * @param Shipping_RateRequest $request Object
     * 
     * @return array Shipping_Quotes
     * @throws Shipping_Error_InvalidInputField
     * 
     */
    public function getQuote(Shipping_RateRequest $request)
    {
        $options = $request->getOptions();
        
        if ($request->getCurrency() != self::SUPPORTED_CURRENCY_CODE) {
            throw new Shipping_Error_UnsupportedCurrency(array('valid_currency' => self::SUPPORTED_CURRENCY_CODE ));
        }
        
        try {
            $rateService = new Shipping_Provider_Endicia_Rate($options);

            $response = $rateService->getRate($request);
            
            return $this->_buildQuotesFromResponse($response, $options);

        } catch (SoapFault $e) {
            throw new Shipping_Error_InvalidInputField(array('reason' => $e->detail->Errors->ErrorDetail->PrimaryErrorCode->Description));
        }
    }
    
    
    /**
     * Construct a list of quotes from the response.
     *
     * @param array $response Response data for the request
     * @param array $options  Request data options
     * 
     * @return rates as Quotes
     * 
     */
    private function _buildQuotesFromResponse($response, $options)
    {
        $quotes = array();

        // parse Response into Quotes Response
        if (! isset($response->PostageRateResponse)) {
            throw new Exception('Provider Returned No Quotes');
        }
        
        $rateResponse = $response->PostageRateResponse;
 
        if (! isset($rateResponse->Postage)) {
            throw new Exception($rateResponse->ErrorMessage);
        }
        
        $quoteDetails = $rateResponse->Postage;
        
        $serviceName = $quoteDetails->MailService;
        
        $rate = $quoteDetails->Rate;
         
        $quotes[] = new Shipping_Quote(new Shipping_Unit((float)$rate, self::SUPPORTED_CURRENCY_CODE), "", $serviceName);

        return $quotes;
    }
    
    
    /**
     * track shipment
     * 
     * @param Shipping_TrackRequest $request object
     *
     * @return array shipmentActivity
     * 
     */
    public function trackShipment(Shipping_TrackRequest $request)
    {
        $options = $request->getOptions();

        try {
       
            $trackService = new Shipping_Provider_Endicia_Track($options);

            $response = $trackService->trackShipment($request);

            $shipmentActivity = $this->_buildTrackDetailsFromResponse($response);

            return $shipmentActivity;

        } catch (Interspire_Http_ClientError $e) {
            throw new Shipping_Error_InvalidInputField($e->getMessage());
        } catch (Interspire_Http_Exception $e) {
            throw new Shipping_Error_ProviderUnavailable($e->getMessage());
        }
    }


    /**
     * TODO: shippmentActivity array needs to be changed as object of ShipmentActivity.
     * It will be done when we are able to successfully extract the date - time from the description
     * 
     * @param array $response tracking response
     *  
     * @return array $shipmentActivity
     *
     */
    private function _buildTrackDetailsFromResponse($response)
    {
        $shipmentActivity = array();

        if (!isset($response->StatusResponse)) {
            throw new Exception('providerReturnedNoTrackingInfo');
        }
        
        if (!is_object($response->StatusResponse->StatusList)) {
            throw new Exception($response->StatusResponse->ErrorMsg);
        }
        
        foreach ($response->StatusResponse->StatusList as $activity) {
            $description = (string)$activity->Status;

            $shipmentActivity[] = new Shipping_ShipmentActivity($description, $this->_getDateTimeFromTrackResponse($description), null);
        }

        return $shipmentActivity;
    }
    
    
    /**
     * Extract date and time from the description
     *  
     * @param String $description Trcaking details
     *  
     * @return DateTime object
     */
    private function _getDateTimeFromTrackResponse($description)
    {
        $datePatterns = array();

        // H:M am/pm on mm/dd/yyyy  | mm-dd-yyyy
        $datePatterns[0] = '/(0?[1-9]|1[012]):([0-5]?[0-9])\s(AM|PM)\son\s(0?[1-9]|1[012])[-|\/](0?[1-9]|[12][0-9]|3[01])[-|\/]((19|20)\\d\\d)/i';

        // H:M am/pm on dd/mm/yyyy | dd-mm-yyyy
        $datePatterns[1] = '/(0?[1-9]|1[012]):([0-5]?[0-9])\s(AM|PM)\son\s(0?[1-9]|[12][0-9]|3[01])[-|\/](0?[1-9]|1[012])[-|\/]((19|20)\\d\\d)/i';

        // H:M am/pm on yyyy/dd/mm | yyyy-dd-mm
        $datePatterns[2] = '/(0?[1-9]|1[012]):([0-5]?[0-9])\s(AM|PM)\son\s((19|20)\\d\\d)[-|\/](0?[1-9]|1[012])[-|\/](0?[1-9]|[12][0-9]|3[01])/i';

        // H:M am/pm on month dd year
        $datePatterns[3] = '/(0?[1-9]|1[012]):([0-5]?[0-9])\s(AM|PM)\son\s(january|february|march|april|may|june|july|august|september|october|november|december)\s(0?[1-9]|[12][0-9]|3[01])\s\s((19|20)\\d\\d)/i';

        $index = 0;
        $matches = array();
        do {
            preg_match($datePatterns[$index], $description, $matches);
            $index++;
        } while (sizeof($matches)<1 && $index<sizeof($datePatterns));
            
        if (sizeof($matches)>0) {
            return new DateTime(str_replace("on", "", (string)$matches[0]));
        } else {
            throw new Exception('noDateFoundInResponse');
        }
    }


    /**
     * return tracking Link
     * 
     * @param string $trackingNumber Request Number tracking
     * 
     * @return string tracking Url
     * 
     */
    public function getTrackingLink($trackingNumber = "")
    {
        return "http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=".urlencode($trackingNumber);
    }


    /**
     * Request for registration
     * 
     * @param Shipping_RegistrationRequest $request Object
     * 
     * @return array customerId
     * @throws Shipping_Error_InvalidInputField
     * 
     */
    public function requestRegistration(Shipping_RegistrationRequest $request)
    {

        $options = $request->getOptions();

        try {

            $registrationService = new Shipping_Provider_Endicia_Registration($options);
            
            $signUpRequestData = $registrationService->buildSignUpRequest($request);
		
            $signUpRequestUrl = $registrationService->getUrl('UserSignupRequest');
		
		$signUpRequestResponse = $this->_performSignUpRequest($signUpRequestUrl, $signUpRequestData);
				
            $signUpResponse = $this->_processResponse($signUpRequestResponse);

            //After signup process Request for pass phrase change
            if (!is_string($signUpResponse["ErrorMsg"]) && isset($signUpResponse['ConfirmationNumber'])) {                
                $changeRequestData = $registrationService->buildPassPhraseRequest($signUpResponse);

                $changeRequestUrl = $registrationService->getUrl('ChangePassPhraseRequest');

                $changeResponse = $this->_passPhraseChangeRequest($changeRequestUrl, $changeRequestData);

                $requestResponse = $this->_processResponse($changeResponse);

                $requestResponse["Password"] = $options->web_password;

                return $requestResponse;

            } else {
                throw new Shipping_Error_ProviderError($signUpResponse["ErrorMsg"]);
            }

        } catch (SoapFault $e) {
            throw new Shipping_Error_ProviderError($e->faultstring);
        }
    }
    
    
    /**
     * Performs signUp request with the provided data and returns the response body.
     * If the server can not be contacted, a ProviderUnavailable exception will be thrown.
     * Throws an Interspire_Http_ClientError/Interspire_Http_ServerError 40x/50x error.
     * 
     * @param String $signUpRequestUrl  Request Url for the request.
     * @param mixed  $signUpRequestData Request data for the request.
     * 
     * @throws Interspire_Http_Exception
     * @return string Shipping_Error_ProviderUnavailable
     */
    private function _performSignUpRequest($signUpRequestUrl, $signUpRequestData)
    {
        //Setup http client
        $this->_setupHttpClient();

        try {
            $this->httpClient->post($signUpRequestUrl, $signUpRequestData);
	
        } catch (Interspire_Http_NetworkError $e) {
            $this->handleConnectionError($e);
        }

        $this->logHttpClientStats();

        return $this->httpClient->getBody();
    }
    public function accountStatus(Shipping_RegistrationRequest $request)
    {
    	$options = $request->getOptions();
	
	try {
		
            $accountStatusOptions = new Shipping_Provider_Endicia_Registration($options);
           
            $accountStatusRequestData = $accountStatusOptions->buildAccountStatusRequest($request);
		
            $accountStatusRequestUrl = $accountStatusOptions->getUrl('AccountStatusRequest');
				
            $accountStatusRequestResponse = $this->_performAccountStatRequest($accountStatusRequestUrl, $accountStatusRequestData);
			
            $accountStatusResponse = $this->_processResponse($accountStatusRequestResponse);
		
		// tried to get the individual values for the validation of account
		//$arrayValues = $accountStatusResponse;
		//$accountStatus= $arrayValues ["CertifiedIntermediary"]["AccountStatus"];
		//var_dump($accountStatus);
		
		return $accountStatusResponse;
				
    		}catch (SoapFault $e) {
            throw new Shipping_Error_ProviderError($e->faultstring);
    			
    		}
      }
    
    
     private function _performAccountStatRequest($accountStatusRequestUrl, $accountStatusRequestData)
    {
    	        //Setup http client
        $this->_setupHttpClient();
		
        try {
            $this->httpClient->post($accountStatusRequestUrl, $accountStatusRequestData);
	
        } catch (Interspire_Http_NetworkError $e) {
            $this->handleConnectionError($e);
        }

        $this->logHttpClientStats();

        return $this->httpClient->getBody();
    }
   /* 
    private function validateAccount()
    {
    	$objVal= accountStatus($request);
	
	var_dump($objVal);
    }*/
        
    
    /**
     * TO Do changes
     * Performs PassPhrase request with the provided data and returns the response body.
     * If the server can not be contacted, a ProviderUnavailable exception will be thrown.
     * Throws an Interspire_Http_ClientError/Interspire_Http_ServerError 40x/50x error.
     * 
     * @param String $changeRequestUrl  Request Url for the request.
     * @param mixed  $changeRequestData Request data for the request.
     * 
     * @throws Interspire_Http_Exception
     * @return string Shipping_Error_ProviderUnavailable
     */
    private function _passPhraseChangeRequest($changeRequestUrl, $changeRequestData)
    {
        //Setup http client
        $this->_setupHttpClient();

        try {
            $this->httpClient->post($changeRequestUrl, $changeRequestData);
        } catch (Interspire_Http_NetworkError $e) {
            $this->handleConnectionError($e);
        }

        $this->logHttpClientStats();

        return $this->httpClient->getBody();
    }


    /**
     * create 
     *
     * @param Shipping_ShipmentRequest $request Object
     * 
     * @return mixed
     * 
     */
    public function createShipment(Shipping_ShipmentRequest $request)
    {
        $options = $request->getOptions();

        try {

            $shipmentService = new Shipping_Provider_Endicia_Shipment($options);

            $response = $shipmentService->confirmShipment($request);

            $labelRequestResponse = $response->LabelRequestResponse;

            return $labelRequestResponse;

        } catch (Interspire_Http_ClientError $e) {
            throw new Shipping_Error_InvalidInputField($e->getMessage());
        } catch (Interspire_Http_Exception $e) {
            throw new Shipping_Error_ProviderUnavailable($e->getMessage());
        }
    }

}
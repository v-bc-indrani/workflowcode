<?php

/**
 * Resource controller handles calls to the shipping service endpoints.
 */
class ShippingController extends Restful_Controller
{
    private $providerName;
    private $provider = null;
    
    /**
     * Returns an instance of the requested provider metadata.
     *
     * @throws Shipping_Error_ProviderNotFound
     * @return Shipping_Provider An instance of the requested provider.
     */
    protected function loadProvider()
    {
        $provider = Shipping_Provider_Registry::find($this->providerName);
        if (!$provider) {
            throw new Shipping_Error_ProviderNotFound(array(
                'requested_provider' => $this->providerName,
            ));
        }
        return $provider;
    }
    
    
    /**
     * Returns an instance of the requested provider service.
     *
     * @throws Shipping_Error_ProviderNotFound
     * @return Shipping_Service An instance of the requested provider service.
     */
    protected function loadProviderService()
    {
    	 
        $providerService = Shipping_Provider_Registry::findService($this->providerName);
	 // print_r($providerService);
        if (!$providerService) {
        	echo "error";
            throw new Shipping_Error_ProviderNotFound(array(
                'requested_provider' => $this->providerName,
            ));
        }
        return $providerService;
    }
    
    
    /**
     * Retrieves the decoded request body from the request object.
     * The method assumes we want a valid request body and throws an exception if
     * no body was sent with the request.
     *
     * @throws Interspire_Response_BodyNotSupplied If no body exists on the request.
     * @return mixed Request body which has been decoded by the framework.
     */
    protected function retrieveRequestBody()
    {
        // the 'body' param contains a decoded copy of the request body.
        $requestInput = $this->request->getUserParam('body');
        if (!$requestInput) {
            throw new Interspire_Response_BodyNotSupplied();
        }
        return $requestInput;
    }
    
    
    /**
     * 
     */
    protected function collectionResponse($data, $total)
    {
        if (! is_array($data)) {
            // items element on a collection response should always be an array.
            $data = array($data);
        }
        return array(
            'total' => $total,
            'count' => count($data),
            'items' => $data,
        );
    }
    
    
    /**
     * 
     */
    protected function resourceResponse($data)
    {
        return $data;
    }
    
    
    public function __construct(Interspire_Request $request, Interspire_Response $response)
    {
        parent::__construct($request, $response);
        
        $this->providerName = $this->request->getUserParam('provider');
    }
    
    
    /**
     * Resource: /quote/:id
     */
    public function quote()
    {
        $providerService = $this->loadProviderService();
        
        $requestBody = $this->retrieveRequestBody();
        
        $rateRequest = new Shipping_RateRequest($requestBody);
        
        $quotes = $providerService->getQuote($rateRequest);
        
        return $this->collectionResponse($quotes, count($quotes));
    }
    
    
    /**
     * Resource: /providers
     */
    public function providers()
    {
        return Shipping_Provider_Registry::findMatching($this->request->getQuery());
    }
    
    
    /**
     * Resource: /provider/:id
     */
    public function provider()
    {
        $provider = $this->loadProvider();
        return $provider->getObject();
    }
    
    
    /**
     * GET /provider/{id}/license
     * POST /provider/{id}/license
     */
    public function license()
    {
        $providerService = $this->loadProviderService();
        
        if ($this->request->getMethod() == 'GET') {
            $optionsRequest = new Shipping_OptionsRequest((object)array(
                'options' => (object)$this->request->getQuery(),
            ));
            
            return $providerService->requestLicense($optionsRequest);

        } elseif ($this->request->getMethod() == 'POST') {
            
            $optionsRequest = new Shipping_OptionsRequest(
                $this->retrieveRequestBody()
            );
            
            return $providerService->acceptLicense($optionsRequest);
        }
    }
    
    
    /**
     *
     */
    public function account()
    {
       $providerService1 = $this->loadProviderService();
	
        $requestBody = $this->retrieveRequestBody();

        $signInRequest = new Shipping_RegistrationRequest($requestBody);

        $signInDetails = $providerService1->accountStatus($signInRequest);

        return $this->resourceResponse($signInDetails);
    }
    
     public function accountstatus()
    {
      
        $providerService1 = $this->loadProviderService();
	
        $requestBody = $this->retrieveRequestBody();

        $signInRequest = new Shipping_RegistrationRequest($requestBody);

        $signInDetails = $providerService1->accountStatus($signInRequest);

        return $this->resourceResponse($signInDetails);
       
    }
      
     /**
     *
     */
    public function registration()
    {
        $providerService = $this->loadProviderService();

        $requestBody = $this->retrieveRequestBody();

        $registrationRequest = new Shipping_RegistrationRequest($requestBody);

        $registrationDetails = $providerService->requestRegistration($registrationRequest);

        return $this->resourceResponse($registrationDetails);
    }
    
    
    /**
     *
     */
    public function merchantInfo()
    {
        $providerService = $this->loadProviderService();

        $requestBody = $this->retrieveRequestBody();
        
        $infoRequest = new Shipping_OptionsRequest($requestBody);
        
        $merchantDetails = $providerService->getMerchantDetails($infoRequest);
        
        return $this->resourceResponse($merchantDetails);
    }
    
    /**
     * Resource: GET /tracking/:provider/link
     */
    public function link()
    {
        $providerService = $this->loadProviderService();
        
        if (!is_callable(array($providerService, 'getTrackingLink'))) {
            throw new Interspire_Response_ResourceNotFound(array(
                'resource' => 'tracking/' . $this->providerName,
            ));
        }
        
        $query = $this->request->getQuery();

        $id = $query['id'];

        return $this->resourceResponse(array(
            'link' => $providerService->getTrackingLink($id),
        ));
    }
    
    
    /**
     * Resource: /tracking/:provider/activity
     */
    public function activity()
    {
        $providerService = $this->loadProviderService();

        $query = $this->request->getQuery();

        /** activity request contain json arguments 
        $requestBody = $this->retrieveRequestBody();
        $trackRequest = new Shipping_TrackRequest($requestBody);
        */
        
        $trackRequest = new Shipping_TrackRequest((object)array(
            'options' => (object)$query,
            'tracking_id' => $query['tracking_id'],
        ));

        $shipmentActivity = $providerService->trackShipment($trackRequest);

        return $this->resourceResponse($shipmentActivity);
    }
    
    /**
     * Resource: /shipment/:provider
     */
    public function shipment()
    {
        $service = $this->loadProviderService();

        $request = $this->retrieveRequestBody();

        $shipmentRequest = new Shipping_ShipmentRequest($request);

        $labelRequestResponse = $service->createShipment($shipmentRequest);

        $labelResponse = $labelRequestResponse->Label;

        $imageResponse = $labelResponse->Image;

        $response = base64_decode("");

        foreach ($response as $key => $value) {
            foreach ($value as $subkey => $subValue) {
                $response = $response.base64_decode($subValue);
            }
        }

        $this->response->addHeader('Content-Type:image/gif');

        $this->response->setBody($response);

        $this->response->sendResponse();

        $this->response->end();
    }
    
    
}

<?php

abstract class Shipping_Provider_Endicia_ApiClient
{
    protected $config;

    protected $api_deliveryService_names = array(
        'domestic_ground' => 'ParcelSelect',
        'domestic_3day' => 'Priority',
        'domestic_next_day' => 'PriorityExpress',
        'international_priority' => 'PriorityMailInternational',
        'international_express' => 'PriorityMailExpressInternational'
    );

    protected $api_packaging_names = array(
        'custom' => 'Parcel',
        'small_box' => 'SmallFlatRateBox',
        'medium_box' => 'MediumFlatRateBox',
        'large_box' => 'LargeFlatRateBox'
    );

    protected $optionKey_labelprintApiKey_map = array(
        'account_id' => 'AccountID',
        'pass_phrase' => 'PassPhrase',
        'requester_id' => 'RequesterID',
        'date_advance' => 'DateAdvance',
        'cost_center' => 'CostCenter',
        'description' => 'Description',
        'partner_id' => 'PartnerCustomerID',
        'partner_transaction_id' => 'PartnerTransactionID',
        'customs_quantity' => 'CustomsQuantity1',
        'customs_value' => 'CustomsValue1',
        'customs_weight' => 'CustomsWeight1',
        'test' => 'Test',
        'label_size' => 'LabelSize',
        'label_type' => 'LabelType',
        'image_format' => 'ImageFormat',
        'from_name' => 'FromName',
        'from_phone' => 'FromPhone',
        'to_name' => 'ToName',
        'to_phone' => 'ToPhone',
        'to_company' => 'ToCompany',
        'from_zip' => 'FromZIP4'
    );

    protected $optionKey_trackApiKey_map = array(
        'account_id' => 'AccountID',
        'pass_phrase' => 'PassPhrase',
        'test_request' => 'Test'
    );
    
    protected $optionKey_rateApiKey_map = array(
        'requester_id' => 'RequesterID',
    );

    protected $optionKey_signupApiKey_map = array(
        'last_name' => 'LastName',
        'physical_city' => 'PhysicalCity',
        'physical_state' => 'PhysicalState',
        'physical_zipcode' => 'PhysicalZipCode',
        'web_password' => 'WebPassword',
        'pass_phrase' => 'PassPhrase',
        'challenge_question' => 'ChallengeQuestion',
        'challenge_answer' => 'ChallengeAnswer',
        'test_request' => 'Test',
        'partner_id' => 'PartnerId',
        'credit_card_number' => 'CreditCardNumber',
        'credit_card_address' => 'CreditCardAddress',
        'credit_card_city' => 'CreditCardCity',
        'credit_card_state' => 'CreditCardState',
        'credit_card_zip_code' => 'CreditCardZipCode',
        'credit_card_type' => 'CreditCardType',
        'credit_card_exp_month' => 'CreditCardExpMonth',
        'credit_card_exp_year' => 'CreditCardExpYear',
        'payment_type' => 'PaymentType',
        'product_type' => 'ProductType',
        'i_certify' => 'ICertify',
        'override_email_check' => 'OverrideEmailCheck'
    );

    protected $optionKey_passPhraseApiKey_map = array(
        'partner_id' => 'RequesterID',
        'request_id' => 'RequestID',
        'pass_phrase' => 'NewPassPhrase'
    );
    
  
    /**
     * settings
     * 
     * @param Array $config options set
     */
    public function __construct($config)
    {
        $this->config = (object)$config;
    }
    
    
    /**
     * URL pointing to SOAP service.
     * 
     * @param string $serviceName Request service name
     * 
     * @return string
     * 
     */
    public function getUrl($serviceName)
    {
        if (isset($this->config->test_mode)) {
        	
            switch ($serviceName) {
            case 'ChangePassPhraseRequest':
                return ($this->config->test_mode) ? static::TEST_PASSPHRASE_URL : static::PRODUCTION_PASSPHRASE_URL;
                break;
	      case 'AccountStatusRequest':
			return ($this -> config->test_mode) ? static::TEST_SIGNIN_URL : static::PRODUCTION_SIGNIN_URL;
			break;
            default:
                return ($this->config->test_mode) ? static::TEST_URL : static::PRODUCTION_URL;
                break;
            }

        } else {
            throw new Shipping_Error_InvalidInputField("test_mode true or false is required ");
        }
    }


    /**
     * Sending api request
     * 
     * @param String $serviceCall   Method Name
     * @param Array  $requestObject Object
     * 
     * @return response
     * @throws SoapFault
     * @throws Shipping_Error_ProviderUnavailable
     */
    public function sendRequest($serviceCall, $requestObject)
    {
        try {
            $client = new SoapClient($this->getUrl($serviceCall), array('trace' => 1));

            switch ($serviceCall) {
            case 'CalculatePostageRate':
                $response = $client->CalculatePostageRate($requestObject);
                break;
            case 'GetPostageLabel':
                $response = $client->GetPostageLabel($requestObject);
                break;
            default:
                $response = $client->__soapCall($serviceCall, $requestObject);
                break;
            }

            return $response;
        } catch(SoapFault $e) {
             throw new Shipping_Error_ProviderError($e->faultstring);
        }
    }


    /**
     * Construct Request service codes
     * 
     * @param array $frameworkDeliveryServices delivery services
     * 
     * @return api delivery services
     * 
     */
    public function buildApiDeliveryServices($frameworkDeliveryServices)
    {
        $apiDeliveryServices = array();

        foreach ($frameworkDeliveryServices as $frameworkDeliveryService) {
            array_push($apiDeliveryServices, $this->mapServiceName($frameworkDeliveryService));
        }
        return $apiDeliveryServices;
    }


    /**
     * Retun services name based on api mapping details
     *
     * @param string $frameworkDeliveryService delivery service
     * 
     * @return delivery service name
     * 
     */
    public function mapServiceName($frameworkDeliveryService)
    {
        if (array_key_exists($frameworkDeliveryService, $this->api_deliveryService_names)) {
            return $this->api_deliveryService_names[$frameworkDeliveryService];
        } else {
            throw new Shipping_Error_UnsupportedOption(array('delivery_services' => Interspire_Language::translate('Shipping_Error_InvalidDeliveryService_Message', array('delivery_services' => $deliveryService))));
        }
    }


    /**
     * Construct Packaging Type
     * 
     * @param array $frameworkPackagingTypes Packaging types
     * 
     * @return api Packaging Type
     * 
     */
    public function buildApiPackagingType($frameworkPackagingTypes)
    {
        $apiPackagingTypes = array();

        foreach ($frameworkPackagingTypes as $frameworkPackagingType) {
            array_push($apiPackagingTypes, $this->mapPackagingName($frameworkPackagingType));
        }
        return $apiPackagingTypes;
    }


    /**
     * Retun packaging Type based on Mapping details
     * 
     * @param string $frameworkPackagingType Packaging type
     * 
     * @return Packaging Type
     *
     */
    public function mapPackagingName($frameworkPackagingType)
    {
        if (array_key_exists($frameworkPackagingType, $this->api_packaging_names)) {
            return $this->api_packaging_names[$frameworkPackagingType];
        } else {
            throw new Shipping_Error_UnsupportedOption(array('packaging' => Interspire_Language::translate('Shipping_Error_InvalidPackaging_Message', array('packaging' => $packagingType))));
        }
    }


    /**
     * map and validate option keys in the requested data
     * 
     * @param String $mapKeysName MapKeysName
     * @param Array  $requestData Object
     * 
     * @return Array $requestData
     * @throws Shipping_Error_InvalidInputField
     * 
     */
    public function mapAndValidateOptionKeys($mapKeysName, $requestData = array())
    {
        switch ($mapKeysName){
        case 'signup':
            $mapKeysSet = $this->optionKey_signupApiKey_map;
            break;
        case 'passPhrase':
            $mapKeysSet = $this->optionKey_passPhraseApiKey_map;
            break;
        case 'rate':
            $mapKeysSet = $this->optionKey_rateApiKey_map;
            break;
        case 'shipment':
            $mapKeysSet = $this->optionKey_labelprintApiKey_map;
            break;
        case 'track':
            $mapKeysSet = $this->optionKey_trackApiKey_map;
            break;
        default:
            $mapKeysSet = array();
            break;
        }

        foreach ($mapKeysSet as $optionKey => $apiKey) {
            if (isset($this->config->$optionKey)) {
                $requestData[$apiKey] = $this->config->$optionKey;
            } else {
                throw new Shipping_Error_InvalidInputField($optionKey. " is required ");
            }
        }
        return $requestData;
    }

}
<?php

class Shipping_Provider_Endicia_Registration extends Shipping_Provider_Endicia_ApiClient
{
    const TEST_URL = "https://elstestserver.endicia.com/ELS/ELSServices.cfc?wsdl";

    const PRODUCTION_URL = "https://www.endicia.com/ELS/ELSServices.cfc?wsdl";

    const TEST_PASSPHRASE_URL = "https://elstestserver.endicia.com/LabelService/EwsLabelService.asmx/ChangePassPhraseXML";

    const PRODUCTION_PASSPHRASE_URL = "https://www.envmgr.com/LabelService/EwsLabelService.asmx/ChangePassPhraseXML";
    
    const TEST_SIGNIN_URL="https://elstestserver.endicia.com/LabelService/EwsLabelService.asmx/GetAccountStatusXML";
    
    const PRODUCTION_SIGNIN_URL="https:///www.envmgr.com/LabelService/EwsLabelService.asmx/GetAccountStatusXML";
  
    /**
     * Construct registration Request
     * 
     * @param Shipping_RegistrationRequest $request Object
     * 
     * @return Xml request object
     * 
     */
    public function buildSignUpRequest(Shipping_RegistrationRequest $request)
    {
        $userSignupRequest = $this->mapAndValidateOptionKeys("signup");
	  
        // build resource request values
        $userSignupRequest["FirstName"] = $request->getCustomerName();

        $userSignupRequest["EmailAddress"] = $request->getEmail();

        $userSignupRequest["EmailConfirm"] = $request->getEmail();

        $userSignupRequest["PhoneNumber"] = $request->getPhone();

        $userSignupRequest["PhysicalAddress"] = $request->getAddress();

        $xml = array("UserSignupRequest"=>$userSignupRequest);
	
        $xmlRequest = Interspire_Xml::encode($xml);
	
        $xmlRequest =  "&method=UserSignup&XMLInput=" . $xmlRequest;
 
        return $xmlRequest;
    }
    public function buildAccountStatusRequest(Shipping_RegistrationRequest $request)
    {
    	 $options= $request->getOptions();
	 echo" in the build account status"."\n";
	// print_r($options);
	 
    	$accountStatus = array ("RequesterID"=> $options->requester_id,
       "RequestID" => $options->request_id,
       "CertifiedIntermediary" => array("AccountID" => $options->account_id,"PassPhrase" => $options->pass_phrase));
	 
	 $xml= array( "AccountStatusRequest"=> $accountStatus);
	 //print_r ($xml);
	 
	 $xmlRequest=Interspire_Xml::encode($xml);
	 $xmlRequest = "&accountStatusRequestXML=" .$xmlRequest;
       // print_r($xmlrequest);
	  return $xmlRequest;
        
    }
  
    /**
     * Construct Pass Phrase Request
     * 
     * @param Array $signUpResponse Object
     * 
     * @return Xml request object
     * 
     */
    public function buildPassPhraseRequest($signUpResponse)
    {
        $passPhraseRequest = $this->mapAndValidateOptionKeys("passPhrase");
        
        $certifiedIntermediaryArray = array();

        $certifiedIntermediaryArray["AccountID"] = $signUpResponse["ConfirmationNumber"];

        $certifiedIntermediaryArray["PassPhrase"] = $this->config->pass_phrase;

        $passPhraseRequest["CertifiedIntermediary"] = $certifiedIntermediaryArray;

        $xml = array("ChangePassPhraseRequest"=>$passPhraseRequest);

        $xmlRequest = Interspire_Xml::encode($xml);

        $xmlRequest =  "&changePassPhraseRequestXML=" . $xmlRequest;

        return $xmlRequest;
    }
    
}
<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

class CreditCard extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"cardName"				=> "string",
        									"cardNumber"			=> "string",
        									"cardSecurityNumber"	=> "string",
        									"cardToken"				=> "string",
        									"cardType"				=> "enum.credit-card-type",
        									"expiryMonth"			=> "string",
       										"expiryYear"			=> "string"						
									];
			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
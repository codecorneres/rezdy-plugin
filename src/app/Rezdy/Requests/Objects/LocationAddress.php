<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class LocationAddress extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"addressLine"					=> "string",
											"addressLine2"					=> "string",
											"city"							=> "string",
											"countryCode"					=> "string",
                							"latitude"						=> "numeric",                							
                							"longitude"						=> "numeric",
                							"postCode"						=> "string",
                							"state"							=> "string"                												
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
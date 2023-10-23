<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class PickupLocation extends BaseRequest {

		public function __construct($params = '') {
			
			$this->requiredParams = [		"locationName"					=> "string",
									];

			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"additionalInstructions"		=> "string",
               								"address"						=> "string",
                							"latitude"						=> "numeric",
                							"locationName"					=> "string",
                							"longitude"						=> "numeric",
                							"minutesPrior"					=> "integer",
                							"pickupInstructions"			=> "string",
                							"pickupTime"					=> "date-time"    								
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}

		public function isValid() {
			return $this->isValidRequest();
		}
}
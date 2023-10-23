<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class CreatedBy extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"code"			=> "string",
        									"email"			=> "string",
        									"firstName"		=> "string",
       										"lastName"		=> "string"								
									];
			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
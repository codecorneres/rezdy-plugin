<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\\Requests\BaseRequest;

class Field extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"fieldType"				=> "enum.field-type",
            								"label"					=> "string",
            								"listOptions"			=> "string",
            								"requiredPerBooking"	=> "boolean",
            								"requiredPerParticipant"=> "boolean",
            								"value"					=> "string",
            								"visiblePerBooking"		=> "boolean",
            								"visiblePerParticipant"	=> "boolean"				
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
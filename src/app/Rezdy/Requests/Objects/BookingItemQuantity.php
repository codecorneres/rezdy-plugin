<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class BookingItemQuantity extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		"optionId"		=> "integer",
                    						"optionLabel"	=> "string",
                    						"optionPrice"	=> "float",
                    						"value"			=> "integer"  								
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
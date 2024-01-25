<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class BookingItem extends BaseRequest {

		public function __construct($params = '') {

			$this->restrictedParams = [	"extras"			=> "array",
										"participants"		=> "array",	];

			//Set the optional properties of the object and the required type
			$this->optionalParams = [	"amount"			=> "numeric",
            							"endTime"			=> "ISO8601",
            							"endTimeLocal"		=> "date-time",   
            							"productCode"		=> "string",
           								"productName"		=> "string", 
           								"startTime"			=> "ISO8601",
            							"startTimeLocal"	=> "date-time",
            							"subtotal"			=> "numeric",
            							"totalItemTax"		=> "numeric",
            							"totalQuantity"		=> "integer",
            							"transferFrom"		=> "string",
            							"transferReturn"	=> "boolean",
            							"transferTo"		=> "string" ];

			// Sets the class mapping for single set items to the request 
			$this->setClassMap =	[ 	'CC_RezdyAPI\Rezdy\Requests\Objects\PickupLocation'		=> 'pickupLocation'
									]; 

			//Sets the class mapping for multiple item sets to the request 				
			$this->addClassMap  =	[	'CC_RezdyAPI\Rezdy\Requests\Extra'						=> 'extras',
										'CC_RezdyAPI\Rezdy\Requests\Objects\BookingItemQuantity'=> 'quantities'
									];	

			$this->extras = [];
			$this->participants = [];
			if (is_array($params)) {
				$this->buildFromArray($params);
			}


		}		
}
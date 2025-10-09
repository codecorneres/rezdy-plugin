<?php
namespace CC_RezdyAPI\Rezdy\Responses;

use CC_RezdyAPI\Rezdy\Requests\EmptyRequest;

class ResponseStandard extends BaseResponse {

	public $itemType;
	public $requestStatus;

	public function __construct($response, $itemType) {
		
		$this->itemType = $itemType;

		$this->parseResponse($response);

		if ($this->wasSuccessful()) {
			$this->$itemType = $this->responseBody->$itemType;
			$this->requestStatus = $this->responseBody->requestStatus;
		}
	}

	public function toRequest() {
		$typeToClass = 	[	'booking'		=> 'CC_RezdyAPI\Rezdy\Requests\Booking',
							'session'		=> 'CC_RezdyAPI\Rezdy\Requests\SessionUpdate',
							'customer'		=> 'CC_RezdyAPI\Rezdy\Requests\Customer',
							'extra'			=> 'CC_RezdyAPI\Rezdy\Requests\Extra',
							'pickupList'	=> 'CC_RezdyAPI\Rezdy\Requests\PickupList',
							'product'		=> 'CC_RezdyAPI\Rezdy\Requests\Product',
						];
		$itemType = $this->itemType;
		if (array_key_exists($itemType, $typeToClass)) {
			$request = new $typeToClass[$itemType];
			$request->assemble($this->$itemType);
		} else {
			$request = new EmptyRequest;
		}		
		return $request;
	}
}
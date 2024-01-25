<?php
namespace CC_RezdyAPI\Rezdy\Responses;

class ResponseList extends BaseResponse {

	public $listType;

	public $requestStatus;

	public function __construct($response, $listType) {

		$this->listType = $listType;
		
		$this->parseResponse($response);

		if ($this->wasSuccessful()) {
			$this->$listType = $this->responseBody->$listType;
			$this->requestStatus = $this->responseBody->requestStatus;
		}
	}

}
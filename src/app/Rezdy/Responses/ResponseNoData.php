<?php
namespace CC_RezdyAPI\Rezdy\Responses;

class ResponseNoData extends BaseResponse {

	public $message;

	public function __construct($response) {
		
		$this->message = $response;

	}

}
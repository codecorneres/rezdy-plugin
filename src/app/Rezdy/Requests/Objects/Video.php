<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class Video extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		'id'			=> 'integer',
											'platform'		=> 'string',
											'url'			=> 'string',		
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
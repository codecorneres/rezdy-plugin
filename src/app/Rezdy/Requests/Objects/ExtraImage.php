<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class ExtraImage extends BaseRequest {

		public function __construct($params = '') {			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [	"id"			=> "integer",
                        				"itemUrl"		=> "string",
                        				"largeSizeUrl"	=> "string",
                        				"mediumSizeUrl"	=> "string",
                        				"thumbnailUrl"	=> "string"
									];
			
			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
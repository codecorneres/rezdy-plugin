<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class SeoTag extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		'id'			=> 'integer',
											'attrKey'		=> 'string',
											'attrValue'		=> 'string',
        									'metaType'		=> 'string',
        									'productCode'	=> 'string',				
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
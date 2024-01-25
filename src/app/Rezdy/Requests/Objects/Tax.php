<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class Tax extends BaseRequest {

		public function __construct($params = '') {
			
			//Set the optional properties of the object and the required type
			$this->optionalParams = [		'compound'		=> 'boolean',
											'label'			=> 'string',
											'priceInclusive'=> 'boolean',
											'supplierId'	=> 'integer',
											'taxAmount'		=> 'numeric',
											'taxPercent'	=> 'numeric',
        									'taxType'		=> 'enum.tax-types',			
									];

			if (is_array($params)) {
				$this->buildFromArray($params);
			}	
		}
}
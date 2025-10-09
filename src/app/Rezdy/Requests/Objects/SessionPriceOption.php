<?php

namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;
use CC_RezdyAPI\Rezdy\Requests\RequestInterface;

class SessionPriceOption extends BaseRequest implements RequestInterface
{

	public function __construct($params = '')
	{

		//Set the optional properties of the object and the required type
		$this->optionalParams = array(
			'id' 			=> 'integer',
			'label'			=> 'string',
			'maxQuantity'	=> 'integer',
			'minQuantity'	=> 'integer',
			'price'			=> 'numeric',
			'priceGroupType' => 'enum-price-group-type'
		);

		if (is_array($params)) {
			$this->buildFromArray($params);
		}
	}

	public function isValid()
	{
		return $this->isValidRequest();
	}
}

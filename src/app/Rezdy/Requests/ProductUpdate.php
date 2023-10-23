<?php

namespace CC_RezdyAPI\Rezdy\Requests;


class ProductUpdate extends BaseRequest implements RequestInterface
{

	public function __construct($params = '')
	{

		//Set the optional properties of the object and the required type
		$this->optionalParams = [
			'advertisedPrice'				=>	'numeric',
			'confirmMode'					=>  'enum.confirm-modes',
			'confirmModeMinParticipants'	=> 	'integer',
			'description'					=>  'string|100-15000',
			'durationMinutes'				=>  'integer',
			'minimumNoticeMinutes'			=>	'integer',
			'name'							=>  'string',
			'pickupId'						=>	'integer',
			'shortDescription'				=>	'string|15-240',
			'terms'							=>	'string'
		];

		//Sets the class mapping for multiple item sets to the request 				
		$this->addClassMap  = 	['CC_RezdyAPI\Rezdy\Requests\Objects\Field'	=> 'bookingFields'];


		$this->addClassMap  =     [
			'CC_RezdyAPI\Rezdy\Requests\Objects\Field'                => 'bookingFields',
			'CC_RezdyAPI\Rezdy\Requests\Extra'                        => 'extras',
			'CC_RezdyAPI\Rezdy\Requests\Objects\Image'                => 'images',
			'CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption'        => 'priceOptions',
			'CC_RezdyAPI\Rezdy\Requests\Objects\SeoTag'                => 'productSeoTags',
			'CC_RezdyAPI\Rezdy\Requests\Objects\Tax'                => 'taxes',
			'CC_RezdyAPI\Rezdy\Requests\Objects\Video'                => 'videos',
		];

		if (is_array($params)) {
			$this->buildFromArray($params);
		}

		// These are required
		$this->bookingFields = array();
		$this->priceOptions = array();
		$this->images = array();
	}

	public function isValid()
	{

		return $this->isValidRequest();
	}
}

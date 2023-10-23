<?php
namespace CC_RezdyAPI\Rezdy\Requests\Objects;

use CC_RezdyAPI\Rezdy\Requests\BaseRequest;

class Participant extends BaseRequest {

	public function __construct($params = '') {
		// Create Fields Array
		$this->fields = [];
	}		

	public function addFields($data) {
		if (is_array($data)) {
			foreach ($data as $item) {
				$this->addFields($item);
			}
		} elseif (get_class($data) == 'CC_RezdyAPI\Rezdy\Requests\Objects\Field') {
			$this->fields[] = $data;
		}		
	}
}
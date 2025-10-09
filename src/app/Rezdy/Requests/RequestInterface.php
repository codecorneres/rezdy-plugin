<?php
namespace CC_RezdyAPI\Rezdy\Requests;

use CC_RezdyAPI\Rezdy\Exceptions\RezdyException;

interface RequestInterface {
	
	// Validation Function
	public function isValid();
	
	// Error Handling
	public function appendTransferErrors(RezdyException $e);
	public function viewErrors();
	
	// Add Information Settings
	public function set($data, string $key = null);
	public function attach($data);
	
	// Output Formats
	public function toJSON();
	public function toArray();
}
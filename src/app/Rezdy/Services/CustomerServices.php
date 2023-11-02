<?php

namespace CC_RezdyAPI\Rezdy\Services;

use CC_RezdyAPI\Rezdy\Util\Config;

use CC_RezdyAPI\Rezdy\Requests\Customer;
use CC_RezdyAPI\Rezdy\Requests\SimpleSearch;

use CC_RezdyAPI\Rezdy\Responses\ResponseStandard;
use CC_RezdyAPI\Rezdy\Responses\ResponseList;
use CC_RezdyAPI\Rezdy\Responses\ResponseNoData;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7;

/**
 * Performs all actions pertaining to Rezdy API Customer Service Calls
 *
 * @package Services
 * @author Brad Ploeger
 */
class CustomerServices extends BaseService
{
    /**
     * Create a new customer      
     *
     * @param CC_RezdyAPI\Rezdy\Requests\Customer $request
     * @return CC_RezdyAPI\Rezdy\Responses\ResponseStandard
     * @throws CC_RezdyAPI\Rezdy\Requests\Customer     
     */
    public function create(Customer $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.customer_create');
        // Verify the request.
        if (!$request->isValid()) return $request;
        try {
            // Try to Send the request              
            $response = parent::sendRequestWithBody('POST', $baseUrl, $request);
        } catch (TransferException $e) {
            // Handle Transfer Exceptions            
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response
        return new ResponseStandard($response->getBody(), 'customer');
    }
    /**
     * Load an existing customer by Id   
     *
     * @param int $customerId 
     * @return CC_RezdyAPI\Rezdy\Responses\ResponseStandard
     * @throws CC_RezdyAPI\Rezdy\Requests\EmptyRequest
     */
    public function get(int $customerID)
    {
        // Build the Request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.customer_get') . $customerId;
        try {
            // Try to Send the request  
            $response = parent::sendRequestWithOutBody('GET', $baseUrl);
        } catch (TransferException $e) {
            // Handle Transfer Exceptions  
            return $this->returnExceptionAsErrors($e);
        }
        // Return the ResponseStandard
        return new ResponseStandard($response->getBody(), 'customer');
    }
    /**
     * Delete a customer
     *
     * @param string $customerId 
     * @return CC_RezdyAPI\Rezdy\Responses\ResponseNoData
     * @throws CC_RezdyAPI\Rezdy\Requests\EmptyRequest  
     */
    public function delete(string $customerId)
    {
        // Build the Request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.customer_delete') . $customerId;
        try {
            // Try to Send the request  
            $response = parent::sendRequestWithoutBody('DELETE', $baseUrl);
        } catch (TransferException $e) {
            // Handle Transfer Exceptions 
            return $this->returnExceptionAsErrors($e);
        }
        // Return the ResponseNoData
        return new ResponseNoData('The customer was successfully deleted');
    }
    /**
     * Search customers in the account
     *
     * @param CC_RezdyAPI\Rezdy\Requests\SimpleSeach $request
     * @return CC_RezdyAPI\Rezdy\Responses\ResponseList
     * @throws CC_RezdyAPI\Rezdy\Requests\SimpleSearch
     */
    public function search(SimpleSearch $request)
    {
        // Build the Request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.customer_search');
        // Verify the request has the minimum information required prior to submission.
        if (!$request->isValid()) return $request;
        try {
            // Try to Send the Request
            $response = parent::sendRequestWithOutBody('GET', $baseUrl, $request->toArray());
        } catch (TransferException $e) {
            // Handle Transfer Exceptions           
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the ResponseList
        return new ResponseList($response->getBody(), 'customers');
    }
}

<?php

namespace CC_RezdyAPI\Rezdy\Services;

use CC_RezdyAPI\Rezdy\Util\Config;

use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\EmptyRequest;
use CC_RezdyAPI\Rezdy\Responses\ResponseStandard;
use CC_RezdyAPI\Rezdy\Responses\ResponseNoData;
use CC_RezdyAPI\Rezdy\Responses\ResponseList;

use CC_RezdyAPI\GuzzleHttp\Exception\TransferException;
use CC_RezdyAPI\GuzzleHttp\Psr7;

/**
 * Performs all actions pertaining to the Rezdy API Availability Service Calls
 *
 * @package Services
 * @author Brad Ploeger
 */
class AvailabilityServices extends BaseService
{
    /**
     * Create a new session - creates availability for a specific start time.
     *
     * NOTE: Sessions can be created only for INVENTORY mode products.
     *
     * @param Rezdy\Requests\SessionCreate $request
     * @return Rezdy\Responses\ResponseStandard
     * @throws Rezdy\Requests\SessionCreate
     */
    public function create(SessionCreate $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_create');
        try {
            // Try to send the request  
            $response = parent::sendRequestWithBody('POST', $baseUrl, $request);
        } catch (TransferException $e) {
            // Handle a TransferException   
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response 
        return new ResponseStandard($response->getBody(), 'session');
    }
    /**
     * Update availability for a specific session.
     *
     * @param Rezdy\Requests\SessionUpdate $request
     * @return Rezdy\Responses\ResponseStandard
     * @throws Rezdy\Requests\SessionUpdate   
     */
    public function update(SessionUpdate $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_update') . $request->sessionId;
        try {
            // Try to send the request  
            $response = parent::sendRequestWithBody('PUT', $baseUrl, $request);
        } catch (TransferException $e) {
            // Handle a TransferException   
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response
        return new ResponseStandard($response->getBody(), 'session');
    }
    /**
     * Delete a single session.
     * @param int $sessionId
     * @return Rezdy\Responses\ResponseStandard
     * @throws Rezdy\Requests\EmptyRequest
     */
    public function delete(int $sessionId)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_delete') . $sessionId;
        try {
            // Try to send the request  
            $response = parent::sendRequestWithoutBody('DELETE', $baseUrl);
        } catch (TransferException $e) {
            // Handle a TransferException   
            return $this->returnExceptionAsErrors($e);
        }
        // Return the Response
        return new ResponseNoData('The session was successfully deleted');
    }
    /**
     * Load availability information for a specific date range.
     *
     * NOTE: This will return a list of sessions, including their availability and 
     * pricing details.  Pricing in the session can be different than the pricing 
     * of the products, in a case when a supplier overrides a price for a specific 
     * session or a ticket type.  Since Rezdy introduced shared availability option 
     * for products, the product sessions can contain price overrides for all of the 
     * products, which share the sessions. Therefore it is necessary to filer only 
     * the price options matching the chosen product code on the client side, 
     * when processing /availability service responses.
     *
     * @param Rezdy\Requests\SessionSearch $request
     * @return Rezdy\Responses\ResponseList
     * @throws Rezdy\Requests\SessionSearch   
     */
    public function search(SessionSearch $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_search');
        try {
            // Try to send the request
            $response = parent::sendRequestWithOutBody('GET', $baseUrl, $request->toArray());
        } catch (TransferException $e) {
            // Handle a TransferException  
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response
        return new ResponseList($response->getBody(), 'sessions');
    }

    public function availability_search(SessionCreate $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.update_availability_search');
        try {
            // Try to send the request  
            $response = parent::sendRequestWithBody('POST', $baseUrl, $request);
        } catch (TransferException $e) {
            // Handle a TransferException   
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response 
        return new ResponseStandard($response->getBody(), 'sessions');
    }





    public function update_availability_batch(SessionCreate $request)
    {
        // Build the request URL
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.update_availability_batch');
        try {
            // Try to send the request  
            $response = parent::sendRequestWithBody('POST', $baseUrl, $request);
        } catch (TransferException $e) {
            // Handle a TransferException   
            return $this->returnExceptionAsErrors($e, $request);
        }
        // Return the Response 
        return new ResponseStandard($response->getBody(), 'session');
    }
}

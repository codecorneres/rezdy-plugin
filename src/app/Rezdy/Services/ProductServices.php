<?php

namespace CC_RezdyAPI\Rezdy\Services;

use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Util\Config;
use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;

use CC_RezdyAPI\Rezdy\Responses\ResponseStandard;
use CC_RezdyAPI\Rezdy\Responses\ResponseList;
use CC_RezdyAPI\Rezdy\Responses\ResponseNoData;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7;

class ProductServices extends BaseService
{

    public function create(Product $request)
    {
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.product_create');

        if (!$request->isValid()) return $request;


        try {

            $response = parent::sendRequestWithBody('POST', $baseUrl, $request);

        } catch (TransferException $e) {

            return $this->returnExceptionAsErrors($e, $request);
        }

        return new ResponseStandard($response->getBody(), 'product');
    }

    public function get(string $productCode)
    {

        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.product_get') . $productCode;

        try {

            $response = parent::sendRequestWithBody('GET', $baseUrl);
        } catch (TransferException $e) {

            return $this->returnExceptionAsErrors($e);
        }

        return new ResponseStandard($response->getBody(), 'product');
    }

    public function update(string $productCode, ProductUpdate $request)
    {

        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.product_get') . $productCode;

        try {

            $response = parent::sendRequestWithBody('PUT', $baseUrl, $request);
        } catch (TransferException $e) {

            return $this->returnExceptionAsErrors($e, $request);
        }

        return new ResponseStandard($response->getBody(), 'product');
    }

    public function delete()
    {
    }

    public function search(string $productName)
    {
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.product_search') . '?search=' . urlencode($productName);
        //App::custom_logs($baseUrl);
        try {

            $response = parent::sendRequestWithBody('GET', $baseUrl);
            //App::custom_logs($response);
        } catch (TransferException $e) {

            return $this->returnExceptionAsErrors($e);
        }

        return new ResponseStandard($response->getBody(), 'product');
    }

    public function searchMarketplace()
    {
    }


    public function image_update(string $productCode, ProductUpdate $request)
    {
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.product_get') . $productCode;

        try {
            // Initialize Guzzle client
            $client = new Client();

            // Prepare the form data
            $formData = $request->all(); // Modify this to suit your form data

            // Make the PUT request with multipart/form-data
            $response = $client->request('PUT', $baseUrl, [
                'multipart' => [
                    [
                        'name' => 'field_name', // Replace with the field name
                        'contents' => 'field_value' // Replace with the field value
                    ],
                    // Add more fields as needed
                ]
            ]);
        } catch (TransferException $e) {
            // Handle the error case here
            return $this->returnExceptionAsErrors($e, $request);
        }

        return new ResponseStandard($response->getBody(), 'product');
    }
}

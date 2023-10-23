<?php

namespace CC_RezdyAPI\Rezdy\Services;

use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Util\Config;

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

    public function search()
    {
    }

    public function searchMarketplace()
    {
    }
}

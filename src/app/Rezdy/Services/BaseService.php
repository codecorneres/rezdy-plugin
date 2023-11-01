<?php
namespace CC_RezdyAPI\Rezdy\Services;

use CC_RezdyAPI\Rezdy\Exceptions\RezdyException;

use CC_RezdyAPI\Rezdy\Requests\EmptyRequest;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;

abstract class BaseService {

    private $client;
    private $apiKey;

    public function __construct(string $apiKey, ClientInterface $client = null) {
        $this->apiKey = $apiKey;
        $this->client = $client ?: new Client();
    }

    protected function getClient() {
        return $this->client;
    }

    protected function sendRequestWithoutBody(string $method, string $baseUrl, array $queryParams = array()) {        

        $queryParams["apiKey"] = $this->apiKey;  

        $query = $this->buildQueryString($queryParams);      

        $request = new Request($method, $baseUrl);        

        return $this->client->send($request, [
            'query' => $query,
        ]);
    }

    protected function sendRequestWithBody(string $method, string $baseUrl, $body = null, array $queryParams = array()) {
        $queryParams["apiKey"] = $this->apiKey;

        $request = new Request($method, $baseUrl);            

        return $this->client->send($request, [
            'query' => $queryParams,
            'json' => $body
        ]);    
    }

    protected function convertException($exception) {

         if ($exception instanceof ClientException || $exception instanceof ServerException) {
            $rezdyException = new RezdyException($exception->getResponse()->getReasonPhrase(), $exception->getCode());
        } else {
            $rezdyException = new RezdyException("Something went wrong", $exception->getCode());
        }

        $rezdyException->setUrl($exception->getRequest()->getUri());

        $errors = $exception->getResponse()->getBody()->getContents();

        $rezdyException->setErrors(json_decode($errors));
        return $rezdyException;
    }

    protected function returnExceptionAsErrors(TransferException $e, $request = null) {

         // See if a request was passed, if a request was not passed create an empty request.
        $request = $request ?: new EmptyRequest;
       
        $rezdyException = $this->convertException($e);

        $request->appendTransferErrors($rezdyException);

        $request->hadError = true;

        return $request;
    }

    private function buildQueryString(array $queryParams) {

        $query = '';        

        foreach ($queryParams as $index => $param) {
            // Check if it is an array
            if (is_array($param)) {
                // Parse the inner array
                foreach ($param as $key => $value) {
                    // Append the key and value to the query
                    $query .= $key . "=" . $value . '&';                                     
                }    
            } else {
                // Append the key and value to the query
                $query .= $index . "=" . $param . '&';
            }                    
        }

        return trim($query, '&');
    }

    protected function parseOptionalArray(array $optionalArray, $default) { }
}
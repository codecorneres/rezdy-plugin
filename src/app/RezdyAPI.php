<?php

namespace CC_RezdyAPI;

use CC_RezdyAPI\Rezdy\Services\ProductServices;
use CC_RezdyAPI\Rezdy\Services\AvailabilityServices;
use CC_RezdyAPI\Rezdy\Services\BookingServices;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

class RezdyAPI
{
     /**
      * Handles interaction with availability management
      * @var availability
      */
     public $availability;
     /**
      * Handles interaction with booking management
      * @var bookings
      */
     public $bookings;
     /**
      * Handles interaction with category management
      * @var categories
      */
     public $categories;
     /**
      * Handles interaction with company management
      * @var companies
      */
     public $companies;
     /**
      * Handles interaction with customer management
      * @var customer
      */
     public $customers;
     /**
      * Handles interaction with extras management
      * @var extra
      */
     public $extra;
     /**
      * Handles interaction with manifest management
      * @var manifest
      */
     public $manifest;
     /**
      * Handles interaction with pickupList management
      * @var pickupList
      */
     public $pickupList;
     /**
      * Handles interaction with product management
      * @var product
      */
     public $products;
     /**
      * Handles interaction with rate management
      * @var rates
      */
     public $rates;
     /**
      * Handles interaction with resource management
      * @var resources
      */
     public $resources;
     /**
      * Handles interaction with rezdyConnect management
      * @var rezdyConnects
      */
     public $rezdyConnect;
     /**
      * Handles interaction with voucher management
      * @var vouchers
      */
     public $vouchers;

     public function __construct($apiKey, ClientInterface $client = null)
     {
          // Create a GuzzleHTTP Client if one is not passed   
          $client = $client ?: new GuzzleClient();
          $this->products         = new ProductServices($apiKey, $client);
          $this->availability     = new AvailabilityServices($apiKey, $client);
          $this->bookings         = new BookingServices($apiKey, $client);
     }
}

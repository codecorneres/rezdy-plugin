<?php

namespace CC_RezdyAPI\Rezdy\Util;

class Config
{

    private static $props = [
        /**
         * REST endpoints
         */
        'endpoints' =>  [
            'base_url'                          => '',
            'availability_create'               => 'availability',
            'availability_update'               => 'availability/',
            'availability_delete'               => 'availability/',
            'availability_search'               => 'availability',
            'update_availability_batch'         => 'availability/batch',
            'booking_create'                    => 'bookings',
            'booking_get'                       => 'bookings/',
            'booking_update'                    => 'bookings/',
            'booking_delete'                    => 'bookings/',
            'booking_search'                    => 'bookings',
            'booking_quote'                     => 'bookings/quote',
            'category_search'                   => 'categories',
            'category_get'                      => 'categories/',
            'category_list'                     => 'categories/%s/products',
            'category_add_product'              => 'categories/%s/products/%s',
            'category_remove_product'           => 'categories/%s/products/%s',
            'company_get'                       => 'companies/alias/%s',
            'customer_create'                   => 'customers',
            'customer_get'                      => 'customers/',
            'customer_delete'                   => 'customers/',
            'customer_search'                   => 'customers',
            'extra_create'                      => 'extra',
            'extra_get'                         => 'extra/',
            'extra_update'                      => 'extra/',
            'extra_delete'                      => 'extra/',
            'extra_search'                      => 'extra',
            'manifest_check_in_session'         => 'manifest/checkinSession',
            'manifest_check_in_status'          => 'manifest/checkinSession',
            'manifest_remove_check_in'          => 'manifest/checkinSession',
            'manifest_check_in_item'            => 'manifest/checkinOrderSession',
            'product_create'                    => 'products',
            'product_search'                    => 'products',
            'product_get'                       => 'products/',
            'product_marketplace'               => 'products/marketplace',
            'product_pickups'                   => 'products/%s/pickups',
            'pickup_create'                     => 'pickups',
            'rate_search'                       => 'rates/search',
            'rate_get'                          => 'rates/',
            'rate_product'                      => 'rates/%s/products/%s',
            'resources_add_session'             => 'resources/%s/session/%s',
            'resources_sessions'                => 'resources/%s/sessions',
            'resources_session'                 => 'resources/session',
            'resource_remove'                   => 'resources/%s/session/%s',
            'rezdy_connect'                     => 'products/%s/rezdyConnect',
            'get_voucher'                       => 'vouchers/',
        ],

        'settings' =>   ['version' => '0.1.0',],
    ];

    public static function get($index)
    {
        $index = explode('.', $index);
        return self::getValue($index, self::$props);
    }

    public static function setBaseUrl($baseUrl)
    {
        self::$props['endpoints']['base_url'] = $baseUrl;
    }

    private static function getValue($index, $value)
    {
        if (is_array($index) && count($index)) {
            $current_index = array_shift($index);
        }
        if (is_array($index) && count($index) && is_array($value[$current_index]) && count($value[$current_index])) {
            return self::getValue($index, $value[$current_index]);
        } else {
            if (isset($value[$current_index])) {
                return $value[$current_index];
            } else {
                return $current_index;
            }
        }
    }
}

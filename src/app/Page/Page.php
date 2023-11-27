<?php

namespace CC_RezdyAPI\Page;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionBatchUpdate;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;

class Page
{
    private $pageContext;

    public function __construct(App $pageContext)
    {

        $this->pageContext = $pageContext;

        add_action('save_post', [$this, 'handlePostUpdate'], 10, 3);

        return $this;
    }

    public function handlePostUpdate($post_id, $post, $update)
    {
        //wp_die('Custom error message. Post not updated.');

        // Check for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Define allowed post types
        $allowed_post_types = $this->pageContext::ALLOWED_POST_TYPE;
        
        if (in_array($post->post_type, $allowed_post_types)) {
            
            // Retrieve all post meta values at once
            $post_data = get_post($post_id);
            $meta_keys = ['tour_price', 'rezdy_product_code', 'tg_tour_hour', 'tg_tour_max', 'tg_description', 'section_content'];
            $post_meta = array_map(
                function ($key) use ($post_id) {
                    return get_post_meta($post_id, $key, true);
                },
                $meta_keys
            );
            $description            = get_post_meta($post_id, 'tg_description', true);
            $tour_max               = get_post_meta($post_id, 'tg_tour_max', true);
            $tour_hour              = str_replace(" hrs", "", get_post_meta($post_id, 'tg_tour_hour', true));
            $tour_price             = preg_replace("/[^0-9.]/", "",  get_post_meta($post_id, 'tour_price', true));
            $shortDescription       = get_post_meta($post_id, 'tg_tour_flexible_travel', true);
            $rezdy_product_code     = get_post_meta($post_id, 'rezdy_product_code', true);
            $post_title             = $post_data->post_title;

            $guzzleClient = new RezdyAPI('bbd855b6152a4bcdb9f4ab1eff1c3b94');
            $product_get = $guzzleClient->products->get($rezdy_product_code);

            if (!empty($product_get->product) && $update) {

                $product_update_params = [
                    'name'                          => $post_title,
                    'description'                   => $description,
                    'shortDescription'              => $shortDescription,
                    'productType'                   => 'PRIVATE_TOUR',
                    'durationMinutes'               => $tour_hour * 60,
                ];
                
                $this->product_update($guzzleClient, $rezdy_product_code, $product_update_params, $post_id);
                
                sleep(2);
                
                for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {
                    if (get_post_meta($post_id, "tg_availability_{$i}_start_time", true) && get_post_meta($post_id, "tg_availability_{$i}_end_time", true)) {
                        $startTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true) . ' ' . get_post_meta($post_id, "tg_availability_{$i}_start_time", true)));
                        $endTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true) . ' ' . get_post_meta($post_id, "tg_availability_{$i}_end_time", true)));
                    } else {
                        $startTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true) . ' ' . '00:00:00'));
                        $endTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true) . ' ' . '23:59:59'));
                    }
                
                    $sessionPriceOptionParams = [];
                    for ($p = 0; $p < get_post_meta($post_id, "tg_price_options", true); $p++) {
                        $sessionPriceOptionParams[] = [
                            'price' => get_post_meta($post_id, "tg_price_options_{$p}_price", true),
                            "label" => get_post_meta($post_id, "tg_price_options_{$p}_label", true)
                        ];
                    }

                    
                    $sessionPriceOptions = [];
                    foreach ($sessionPriceOptionParams as $params) {
                        $sessionPriceOptions[] = new PriceOption($params);
                    }

                    $sessionParams = [
                        'productCode'                   => $rezdy_product_code,
                        'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                        'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true) ? true : false,
                        'startTimeLocal'                => $startTimeLocal,
                        'endTimeLocal'                  => $endTimeLocal
                    ];

                    if(get_post_meta($post_id, "tg_availability_{$i}_session_id", true)){
                        $sessionParams['sessionId'] = get_post_meta($post_id, "tg_availability_{$i}_session_id", true);
                        $response = $this->availability_update($guzzleClient, $sessionParams);
                    }else{
                        $response = $this->availability_create($guzzleClient, $sessionParams);
                        update_post_meta($post_id, "tg_availability_{$i}_session_id", $response->session->id);
                    }

                    App::custom_logs($sessionParams);
                }
            } else {
                $productParams = [
                    'description'                    => $description,
                    'durationMinutes'                => $tour_hour * 60,
                    'name'                           => $post_title,
                    'productType'                    => 'PRIVATE_TOUR',
                    'shortDescription'               => $shortDescription,
                ];
                $this->product_create($guzzleClient, $post_id, $productParams);
                sleep(2);
                $rezdy_product_code = get_post_meta($post_id, 'rezdy_product_code', true);
                for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {
                    if (get_post_meta($post_id, "tg_availability_{$i}_start_time", true) && get_post_meta($post_id, "tg_availability_{$i}_end_time", true)) {
                        $startTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true) . ' ' . get_post_meta($post_id, "tg_availability_{$i}_start_time", true)));
                        $endTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true) . ' ' . get_post_meta($post_id, "tg_availability_{$i}_end_time", true)));
                    } else {
                        $startTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true) . ' ' . '00:00:00'));
                        $endTimeLocal = date('Y-m-d H:i:s', strtotime(get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true) . ' ' . '23:59:59'));
                    }
                    $sessionParams = [
                        'productCode'                   => $rezdy_product_code,
                        'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                        'seatsAvailable'                => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                        'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true) ? true : false,
                        'startTimeLocal'                => $startTimeLocal,
                        'endTimeLocal'                  => $endTimeLocal,
                    ];
                    $response = $this->availability_create($guzzleClient, $sessionParams);
                    update_post_meta($post_id, "tg_availability_{$i}_session_id", $response->session->id);
                }
            }
        }

        return $post_id;
    }


    public function product_create($guzzleClient, $post_id, $productParams)
    {
        $product = new Product($productParams);
        $priceOptionParams = [];
        for ($p = 0; $p < get_post_meta($post_id, "tg_price_options", true); $p++) {
            $priceOptionParams[] = [
                'price' => get_post_meta($post_id, "tg_price_options_{$p}_price", true),
                "label" => get_post_meta($post_id, "tg_price_options_{$p}_label", true)
            ];
        }
        $sessionPriceOptions = [];
        foreach ($priceOptionParams as $params) {
            $sessionPriceOptions[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptions as $sessionPriceOption) {
            $product->attach($sessionPriceOption);
        }

        $rezdy_res = $guzzleClient->products->create($product);

        if ($rezdy_res->hadError ==  true) {
            App::sendMail('responseupdate' . json_encode($rezdy_res));

            wp_die(implode(',', $rezdy_res->error));
        }
        update_post_meta($post_id, 'rezdy_product_code', $rezdy_res->product->productCode);
    }

    public function product_update($guzzleClient, $rezdy_product_code, $productParams, $post_id)
    {
        $productUpdate = new ProductUpdate($productParams);
        // $priceOptions = new PriceOption($priceOptionParams);
        // $productUpdate->attach($priceOptions);
        $priceOptionParams = [];
        for ($p = 0; $p < get_post_meta($post_id, "tg_price_options", true); $p++) {
            $priceOptionParams[] = [
                'price' => get_post_meta($post_id, "tg_price_options_{$p}_price", true),
                "label" => get_post_meta($post_id, "tg_price_options_{$p}_label", true)
            ];
        }
        $sessionPriceOptions = [];
        foreach ($priceOptionParams as $params) {
            $sessionPriceOptions[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptions as $sessionPriceOption) {
            $productUpdate->attach($sessionPriceOption);
        }
        return $guzzleClient->products->update($rezdy_product_code, $productUpdate);
    }


    function availability_create($guzzleClient, $sessionParams)
    {
        $session = new SessionCreate($sessionParams);
        return $guzzleClient->availability->create($session);
    }
    function availability_update($guzzleClient, $sessionParams)
    {
        $session = new SessionUpdate($sessionParams);
        return $guzzleClient->availability->update($session);
        // $response = $guzzleClient->availability->update_availability_batch($session);
    }
}

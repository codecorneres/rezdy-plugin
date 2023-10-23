<?php

namespace CC_RezdyAPI\Page;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
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

        if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            // add hooks for page updates
            add_action('save_post', [$this, 'handlePostUpdate'], 999, 3);
        }

        return $this;
    }

    public function handlePostUpdate($post_id, $post, $update)
    {
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
            $guzzleClient = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
            $product_get = $guzzleClient->products->get($rezdy_product_code);

            $array = [];
            for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {

                // $sessionParams = [
                //     'productCode'                   => $rezdy_product_code,
                //     'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                //     'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true),
                //     'startTimeLocal'                => get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true),
                //     'endTimeLocal'                  => get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true),
                //     'startTime'                     => get_post_meta($post_id, "tg_availability_{$i}_start_time", true),
                //     'endTime'                       => get_post_meta($post_id, "tg_availability_{$i}_end_time", true),
                //     // 'priceOptions'                  => $sessionPriceOptionParams
                // ];
            }

            $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];

            $firstKey = key($array);
            end($array);
            $lastKey = key($array);
            reset($array);

            echo "First Key: " . $firstKey . "\n";
            echo "Last Key: " . $lastKey . "\n";



            $prm = [
                'productCode' => $rezdy_product_code,
                'startTimeLocal' => '2023-09-21 00:00:00',
                'endTimeLocal' => '2024-01-01 02:00:00'
            ];
            $sessionSearch = new SessionSearch($prm);
            $product_session_get = $guzzleClient->availability->search($sessionSearch);
            App::sendMail('asdasdasd' . json_encode($product_session_get));
            // die;
            if (!empty($product_get->product)) {

                $priceOptionParams = [
                    'label' => '',
                    'price' => $tour_price
                ];
                $product_update_params = [
                    'name'                          => $post_title,
                    'description'                   => $description,
                    'shortDescription'              => $shortDescription,
                    'productType'                   => 'PRIVATE_TOUR',
                    'durationMinutes'               => $tour_hour * 60,
                    // 'priceOptions'                  => $priceOptions
                ];
                $this->product_update($guzzleClient, $rezdy_product_code, $product_update_params, $priceOptionParams);


                // Create the array of session update parameters 
                $sessionParams = [
                    'sessionId'                     => get_post_meta($post_id, 'tg_availability_session_id', true),
                    'seats'                         => get_post_meta($post_id, 'tg_availability_seats', true),
                    'allDay'                        => get_post_meta($post_id, 'tg_availability_all_day', true),
                ];
                // Create the Session Update Request
                $this->availability_update($guzzleClient, $sessionParams);
            } else {
                $productParams = [
                    'description'                    => $description,
                    'durationMinutes'                => $tour_hour * 60,
                    'name'                           => $post_title,
                    'productType'                    => 'ACTIVITY',
                    'shortDescription'               => $shortDescription,
                ];
                $priceOptionParams = [
                    'price' => $tour_price
                ];
                $this->product_create($guzzleClient, $post_id, $productParams, $priceOptionParams);

                sleep(2);
                $rezdy_product_code = get_post_meta($post_id, 'rezdy_product_code', true);


                for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {

                    //tg_availability_0_price_options_1_price
                    $sessionPriceOptionParams = [];
                    for ($p = 0; $p < get_post_meta($post_id, "tg_availability_{$i}_price_options", true); $p++) {
                        $sessionPriceOptionParams[] = [
                            'price' => get_post_meta($post_id, "tg_availability_{$i}_price_options_{$p}_price", true),
                            "label" => get_post_meta($post_id, "tg_availability_{$i}_price_options_{$p}_label", true)
                        ];
                    }

                    $sessionParams = [
                        'productCode'                   => $rezdy_product_code,
                        'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                        'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true),
                        'startTimeLocal'                => get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true),
                        'endTimeLocal'                  => get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true),
                        'startTime'                     => get_post_meta($post_id, "tg_availability_{$i}_start_time", true),
                        'endTime'                       => get_post_meta($post_id, "tg_availability_{$i}_end_time", true),
                        // 'priceOptions'                  => $sessionPriceOptionParams
                    ];
                    $response = $this->availability_create($guzzleClient, $sessionParams, $sessionPriceOptionParams);
                    App::sendMail('response' . json_encode($response));

                    update_post_meta($post_id, "tg_availability_{$i}_session_id", $response->session->id);
                }
            }
        }

        return $post_id;
    }


    public function product_create($guzzleClient, $post_id, $productParams, $priceOptionParams)
    {
        $product = new Product($productParams);
        $priceOption = new PriceOption($priceOptionParams);
        $product->attach($priceOption);

        $rezdy_res = $guzzleClient->products->create($product);

        update_post_meta($post_id, 'rezdy_product_code', $rezdy_res->product->productCode);
    }

    public function product_update($guzzleClient, $rezdy_product_code, $productParams, $priceOptionParams)
    {
        $productUpdate = new ProductUpdate($productParams);
        $priceOptions = new PriceOption($priceOptionParams);
        $productUpdate->attach($priceOptions);
        $rezdy_res = $guzzleClient->products->update($rezdy_product_code, $productUpdate);
    }


    function availability_create($guzzleClient, $sessionParams, $sessionPriceOptionParams)
    {
        $session = new SessionCreate($sessionParams);


        $sessionPriceOptions = [];
        foreach ($sessionPriceOptionParams as $params) {
            $sessionPriceOptions[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptions as $sessionPriceOption) {
            $session->attach($sessionPriceOption);
        }

        // $sessionPriceOption = new PriceOption($sessionPriceOptionParams);

        // $session->attach($sessionPriceOption);
        $response = $guzzleClient->availability->create($session);
        return $response;
    }
    function availability_update($guzzleClient, $sessionParams)
    {
        $session = new SessionUpdate($sessionParams);
        // Send the request to the API
        $response = $guzzleClient->availability->update($session);
        $response = $guzzleClient->availability->update_availability_batch($session);
    }
}

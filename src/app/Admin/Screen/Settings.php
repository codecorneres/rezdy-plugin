<?php

namespace CC_RezdyAPI\Admin\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Objects\Image;
use CC_RezdyAPI\Rezdy\Requests\Objects\SessionPriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionBatchUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;
use Exception;

class Settings extends Screen
{

    public function render()
    {
        return $this->renderTemplate('settings.php', [
            'nonce' => wp_create_nonce('cc-rezdy-api'),
            'rezdy_api_key' => get_option('cc_rezdy_api_key'),
            'rezdy_api_url' => get_option('cc_rezdy_api_url'),
            'stripe_pub_api_key' => get_option('cc_stripe_pub_api_key'),
            'stripe_secret_api_key' => get_option('cc_stripe_secret_api_key'),
            'success_url' => get_option('cc_success_url'),
            'cancel_url' => get_option('cc_cancel_url'),
        ]);
    }

    public function synch_setting()
    {
        return $this->renderTemplate('synch-setting.php', []);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_style('cc-rezdy-api', "{$base}src/assets/settings.css", [], $this->appContext::SCRIPTS_VERSION);
    }

    public function update()
    {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'cc-rezdy-api'))
            return $this->error(__('Invalid request, authorization check failed. Please try again.', 'cc-rezdy-api'));

        if (isset($_POST['update_rezdy_settings']))
            return $this->updateSettings();


        return $this->success(__('Changes saved successfully.', 'cc-rezdy-api'));
    }

    public function updateSettings()
    {
        if (!$rezdy_api_key = sanitize_text_field($_POST['rezdy_api_key'] ?? ''))
            return $this->error(__('Please enter a Rezdy API Key.', 'cc-rezdy-api'));

        if (!$rezdy_api_url = sanitize_text_field($_POST['rezdy_api_url'] ?? ''))
            return $this->error(__('Please enter a Rezdy API Key.', 'cc-rezdy-api'));

        if (!$stripe_pub_api_key = sanitize_text_field($_POST['stripe_pub_api_key'] ?? ''))
            return $this->error(__('Please enter a Stripe API Key.', 'cc-rezdy-api'));

        if (!$stripe_secret_api_key = sanitize_text_field($_POST['stripe_secret_api_key'] ?? ''))
            return $this->error(__('Please enter a Stripe API Key.', 'cc-rezdy-api'));

        if (!$success_url = sanitize_text_field($_POST['success_url'] ?? ''))
            return $this->error(__('Please select success url.', 'cc-rezdy-api'));

        if (!$cancel_url = sanitize_text_field($_POST['cancel_url'] ?? ''))
            return $this->error(__('Please select cancel url.', 'cc-rezdy-api'));

        update_option('cc_stripe_pub_api_key', $stripe_pub_api_key);
        update_option('cc_stripe_secret_api_key', $stripe_secret_api_key);
        update_option('cc_success_url', $success_url);
        update_option('cc_cancel_url', $cancel_url);

        update_option('cc_rezdy_api_key', $rezdy_api_key);
        update_option('cc_rezdy_api_url', $rezdy_api_url);

        //$this->setRezdyClient($rezdy_api_key);

        return $this->success(__('Rezdy settings updated successfully.', 'cc-rezdy-api'));
    }


    public function setRezdyClient($rezdy_api_key)
    {
        $post_id = 3352;
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

            $guzzleClient = new RezdyAPI($this->appContext::API_KEY);
            $product_get = $guzzleClient->products->get($rezdy_product_code);

            if (!empty($product_get->product)) {

                $product_update_params = [
                    'name'                          => $post_title,
                    'description'                   => $description,
                    'shortDescription'              => $shortDescription,
                    'productType'                   => 'PRIVATE_TOUR',
                    'durationMinutes'               => $tour_hour * 60,
                ];
                
                //$this->product_update($guzzleClient, $rezdy_product_code, $product_update_params, $post_id);
                
                
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
                        'allDay'             => true,
                        'seats'                => 20,
                        'seatsAvailable'    => 5,
                        'sessionId'                     => get_post_meta($post_id, "tg_availability_{$i}_session_id", true)
                    ];
                    
                    echo "<pre>";
                    //print_r($sessionParams);

                    $response = $this->availability_update($guzzleClient, $sessionParams);
                    
                   // $response = $this->availability_create($guzzleClient, $sessionParams);
                    print_r($response);
                    
                    // App::sendMail('response' . json_encode($response));
                    // update_post_meta($post_id, "tg_availability_{$i}_session_id", $response->session->id);
                }

            }
        exit();
    }

    function availability_create($guzzleClient, $sessionParams)
    {
        $session = new SessionCreate($sessionParams);
        // echo "<pre>";
        // print_r($session);
        $response = $guzzleClient->availability->create($session);
        return $response;
    }

    function availability_update($guzzleClient, $sessionParams)
    {
        $session = new SessionUpdate($sessionParams);
        $guzzleClient->availability->update($session);
        // $response = $guzzleClient->availability->update_availability_batch($session);
    }
}

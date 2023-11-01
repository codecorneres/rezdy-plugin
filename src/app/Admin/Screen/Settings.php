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

        $this->setRezdyClient($rezdy_api_key);

        return $this->success(__('Rezdy settings updated successfully.', 'cc-rezdy-api'));
    }


    public function setRezdyClient($rezdy_api_key)
    {
        $guzzleClient = new RezdyAPI($rezdy_api_key);
        // $product_get = $guzzleClient->products->get('asdsd');
        $rezdy_product_code = get_post_meta('5767', 'rezdy_product_code', true);

        echo "<pre>";
        print_r(get_the_post_thumbnail_url('5767'));
        die;

        // $sessionParams = [
        //     // 'sessionId'                     => get_post_meta('5767', "tg_availability_0_session_id", true),
        //     // 'seats'                         => get_post_meta('5767', "tg_availability_0_seats", true),
        //     // 'allDay'                        => get_post_meta('5767', "tg_availability_0_all_day", true),
        // ];
        // $sessionParams = [
        //     'sessionId' => get_post_meta('5767', "tg_availability_0_session_id", true),
        //     'seats' => 500
        // ];

        // // Create the Session Update Request
        // $session = new SessionUpdate($sessionParams);
        // $response = $guzzleClient->availability->update($session);

        $response = [];
        for ($i = 0; $i <  get_post_meta("5767", 'tg_availability', true); $i++) {

            //tg_availability_0_price_options_1_price
            $sessionPriceOptionParams = [];
            for ($p = 0; $p < get_post_meta("5767", "tg_availability_{$i}_price_options", true); $p++) {
                $sessionPriceOptionParams[] = [
                    'price' => get_post_meta("5767", "tg_availability_{$i}_price_options_{$p}_price", true),
                    "label" => get_post_meta("5767", "tg_availability_{$i}_price_options_{$p}_label", true)
                ];
            }

            $sessionParams = [
                'productCode'                   => $rezdy_product_code,
                'seats'                         => get_post_meta("5767", "tg_availability_{$i}_seats", true),
                'allDay'                        => get_post_meta("5767", "tg_availability_{$i}_all_day", true),
                'startTimeLocal'                => get_post_meta("5767", "tg_availability_{$i}_start_time_local", true),
                'endTimeLocal'                  => get_post_meta("5767", "tg_availability_{$i}_end_time_local", true),
                'startTime'                     => get_post_meta("5767", "tg_availability_{$i}_start_time", true),
                'endTime'                       => get_post_meta("5767", "tg_availability_{$i}_end_time", true),
                // 'priceOptions'                  => $sessionPriceOptionParams
            ];
            $session = new SessionBatchUpdate($sessionParams);
            $sessionPriceOptions = [];
            foreach ($sessionPriceOptionParams as $params) {
                $sessionPriceOptions[] = new PriceOption($params);
            }

            foreach ($sessionPriceOptions as $sessionPriceOption) {
                $session->attach($sessionPriceOption);
            }

            $response[] = $guzzleClient->availability->update_availability_batch($session);
        }

        echo "<pre>";
        print_r(new PriceOption());
        die;

        // $prm = [
        //     'productCode' =>   $rezdy_product_code,
        //     'startTimeLocal' => '2023-09-21 00:00:00',
        //     'endTimeLocal' => '2024-01-01 02:00:00'
        // ];
        // $sessionSearch = new SessionSearch($prm);
        // $product_get = $guzzleClient->availability->search($sessionSearch);
        echo "<pre>";
        print_r(get_post_meta('5767'));
        die;


        $sessionParams = [
            'productCode'     => $rezdy_product_code,
            'seats'           => '50',
            'allDay'          => true,
            'startTimeLocal'  => '2023-10-20 23:00:00'
        ];


        $sessionPriceOptionParams = [
            [
                'price' => 1233,
                'label' => 'CHILD'
            ],
            [
                'price' => 45,
                'label' => 'INFANT'
            ]
        ];

        $session = new SessionCreate($sessionParams);

        $sessionPriceOptions = [];
        foreach ($sessionPriceOptionParams as $params) {
            $sessionPriceOptions[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptions as $sessionPriceOption) {
            $session->attach($sessionPriceOption);
        }
        $response = $guzzleClient->availability->create($session);



        echo "<pre>";
        print_r($response);
        exit();
    }
}

<?php

namespace CC_RezdyAPI\Frontend\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Util\Config;
use DateTime;
use DateTimeZone;

class AirwallexKlarna
{
    protected $api_key;
    protected $client_id;
    protected $airwallex_live;
    protected $baseUrl;
    protected $token;
    protected $token_expiration;
    protected $transaction;
    protected $klarna_payment;

    public function __construct()
    {
        $this->api_key = get_option('cc_airwallex_secret_api_key');
        $this->client_id = get_option('cc_airwallex_client_id');
        $this->airwallex_live = get_option('cc_airwallex_live');
        $this->baseUrl = ($this->airwallex_live == 'yes') ? 'https://api.airwallex.com/api/v1/' : 'https://api-demo.airwallex.com/api/v1/';
        $this->exchange_airwallex_token();
    }

    public function generateGUID()
    {
        if (function_exists('com_create_guid')) {
            return strtolower(trim(com_create_guid(), '{}'));
        } else {
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $uuid;
        }
    }

    protected function prepare_klarna_order_itineraries($updatedItemsAirewallex)
    {
        $items = $updatedItemsAirewallex;
        $updatedItemsAirewallex['order']['shipping'] = [
            'email' => $items['customer']['email'] ?? '',
            'first_name' => $items['customer']['firstName'] ?? '',
            'last_name' => $items['customer']['lastName'] ?? '',
            'phone_number' => $items['customer']['phone'] ?? '',
        ];

        $itineraries = [];
        foreach ($_POST['order'] as $index => $order) {
            $date = date('c', strtotime($order['sessionDate']));
            $itineraries[] = [
                "departure_country_code" => "FR",
                "destination_country_code" => "FR",
                "depart_at" => $date,
                "service_class" => "business",
                "price" => $items['order']['products'][$index]['unit_price'] ?? 0,
                "traveler_identifier" => $items['customer']['email'],
            ];
        }
        $updatedItemsAirewallex['order']['itineraries'] = $itineraries;

        return $updatedItemsAirewallex;
    }

    protected function prepare_klarna_order_products($updatedItemsAirewallex)
    {
        foreach ($updatedItemsAirewallex['order']['products'] as $AirwallexItem => $item) {
            if (isset($item['type']) && $item['type'] == 'service') {
                $updatedItemsAirewallex['order']['products'][$AirwallexItem]['type'] = 'intangible_good';
            }
        }

        foreach ($updatedItemsAirewallex['order']['itineraries'] as $AirwallexItem => $item) {
            $updatedItemsAirewallex['order']['products'][$AirwallexItem]['effective_start_at'] = $updatedItemsAirewallex['order']['itineraries'][$AirwallexItem]['depart_at'];
            $updatedItemsAirewallex['order']['products'][$AirwallexItem]['effective_end_at'] = $updatedItemsAirewallex['order']['itineraries'][$AirwallexItem]['depart_at'];
        }

        return $updatedItemsAirewallex;
    }

    public function get_token()
    {
        if (! $this->token || ! $this->token_expiration) {
            return $this->exchange_airwallex_token();
        } else {
            $expiry = new DateTime($this->token_expiration, new DateTimeZone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $now->modify("+30 seconds");

            return ($now >= $expiry) ? $this->exchange_airwallex_token() : $this->token;
        }
    }

    public function exchange_airwallex_token()
    {
        $url = $this->baseUrl . 'authentication/login';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'x-client-id' => $this->client_id
            ],
            'body' => '{}'
        ]);

        if (is_wp_error($response)) {
            error_log(json_encode(['message' => 'Error requesting token']));
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->token = $body['token'] ?? null;
        $this->token_expiration = $body['expires_at'] ?? null;
    }

    public function create_klarna_intent($payload, $inserted_id, $klarna_uid)
    {
        if (! $this->token && defined( 'DOING_AJAX' ) && DOING_AJAX) {
            wp_send_json([
                'isError' => true,
                'message' => 'Failed to get token, invalid API keys',
                'next' => null,
            ]);
            wp_die();
        }

        $payload = $this->prepare_klarna_order_itineraries($payload);
        $payloadModified = $this->prepare_klarna_order_products($payload);

        $url = $this->baseUrl . 'pa/payment_intents/create';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_token(),
            ],
            'body' => json_encode($payloadModified)
        ]);

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $int_id = $body['id'] ?? null;

        if (! $int_id) {
            return [
                'isError' => true,
                'message' => 'Failed to create Airwallex Klarna intent',
                'next' => null,
                'response' => $body
            ];
        }

        $this->update_transaction_data($int_id, $inserted_id, $klarna_uid);

        // prepare the payload for klarna payment intent confirm
        $payload = [
            'request_id' => $this->generateGUID(),
            'payment_method' => [
                'type' => 'klarna',
                'klarna' => [
                    'country_code' => get_valid_klarna_country($_SESSION['po_country'] ?? '', 'FR'),
                    'billing' => [
                        'email' => $payload['customer']['email'] ?? '',
                        'first_name' => $payload['customer']['firstName'] ?? '',
                        'last_name' => $payload['customer']['lastName'] ?? '',
                        'phone_number' => $payload['customer']['phone'] ?? '',
                    ]
                ]
            ],
            'payment_method_options' => [
                'klarna' => [
                    'auto_capture' => true
                ]
            ]
        ];

        return $this->confirm_klarna_intent($int_id, $payload);
    }

    public function confirm_klarna_intent($intent_id, $payload)
    {
        $url = $this->baseUrl . 'pa/payment_intents/' . $intent_id . '/confirm';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_token(),
            ],
            'body' => json_encode($payload)
        ]);

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status = $body['status'] ?? null;

        if (! $status) {
            return [
                'isError' => true,
                'message' => 'Failed to confirm Airwallex Klarna Intent - ' . $status,
                'next' => null,
                'response' => $body
            ];
        } else if ($status == 'REQUIRES_CUSTOMER_ACTION') {
            return [
                'isError' => false,
                'message' => 'Airwallex Klarna Intent confirmed - requires customer action',
                'next' => $body['next_action']['url'],
                'response' => $body
            ];
        } else {
            return [
                'isError' => true,
                'message' => 'Failed to confirm Airwallex Klarna Intent - ' . $status,
                'next' => null,
                'response' => $body
            ];
        }
    }

    protected function update_transaction_data($int_id, $inserted_id, $klarna_uid)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data = [
            'klarna_uid' => $klarna_uid,
            'transactionID' => $int_id
        ];
        $where = [ 'id' => $inserted_id ];
        $data_format = [ '%s', '%s' ];
        $where_format = [ '%d' ];

        $result = $wpdb->update($table, $data, $where, $data_format, $where_format);

        if ($result === false) {
            error_log('Failed to update klarna_uid for row: ' . $inserted_id);
        } else {
            error_log('Transaction updated successfully: ' . $inserted_id);
        }
    }

    public function get_transaction($k_uid)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'rezdy_plugin_transactions';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE klarna_uid = %s LIMIT 1",
                $k_uid
            ),
            ARRAY_A
        );

        return $result;
    }

    public function fetch_payment_and_update_transactions($k_uid)
    {
        $this->transaction  = $this->get_transaction($k_uid);

        if (data_get($this->transaction, 'klarna_rezdy_processed') == 'yes') {
            return;
        }

        $transaction_id = $this->transaction['id'];
        $transaction_int_id = $this->transaction['transactionID'];

        $url = $this->baseUrl . 'pa/payment_intents/' . $transaction_int_id;
        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_token(),
            ]
        ]);

        $this->klarna_payment = $klarna = json_decode(wp_remote_retrieve_body($response), true);
        $status = data_get($klarna, 'status', 'FAILED');
        $total_paid = data_get($klarna, 'base_amount', '00.00');

        if ($status == 'SUCCEEDED') {
            $this->update_transaction($transaction_id, $transaction_int_id, $status, $total_paid);
            $this->create_rezdy_booking_and_update_transaction($transaction_id, $transaction_int_id);
        }
    }

    protected function update_transaction($transaction_id, $transaction_int_id, $status, $total_paid)
    {
        global $wpdb;

        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'success_message' => $status,
            'failure_message' => '',
            'rezdy_order_id' => '',
            'order_status' => 1,
            'response_time' => current_time('mysql'),
            'totalPaid' => $total_paid,
            'klarna_rezdy_processed' => 'yes'
        );

        $where = array(
            'id' => $transaction_id,
            'transactionID' => $transaction_int_id,
        );
        $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Payment status: Airewallex Klarna payment completed" . PHP_EOL . "User name: " . data_get($this->transaction, 'username', '') . PHP_EOL . "User email: " . data_get($this->transaction, 'useremail', '') . PHP_EOL .  "Table inserted_id: " . $transaction_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $log_dir = PLUGIN_DIR_PATH . 'payment_logs/klarna_logs/';
        if (! file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    protected function create_rezdy_booking_and_update_transaction($transaction_id, $transaction_int_id)
    {
        global $wpdb;

        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.booking_create');
        $rezdy_api_key = get_option('cc_rezdy_api_key');
        $apiUrl = $baseUrl;
        $payload = $this->transaction['rezdy_params'];
        $payload = (gettype($payload) == 'string') ? json_decode($payload, true) : $payload;
        $payload['payments'][0]['label'] = "Airwallex Klarna Payment Intent ID: " . $transaction_int_id;
        $payload = json_encode($payload);

        $response = wp_remote_post($apiUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Apikey' => $rezdy_api_key,
                'Cookie' => 'JSESSIONID=19D1B116214696EA41B2579C7080DD81',
            ],
            'body' => $payload,
            'method' => 'POST',
            'timeout' => 30,
        ]);

        // Handle the response
        if (is_wp_error($response)) {
            error_log('Failed to create Rezdy booking for booking : "' . data_get($this->klarna_payment, 'id'));
            error_log($response->get_error_message());
        }

        $response = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Rezdy booking response: ' . json_encode($response));

        // Prepare data for transaction update
        $rezdy_order_id = $response['booking']['orderNumber'];
        $rezdy_booking_status = $response['booking']['status'];
        $rezdy_total_amount = $response['booking']['totalAmount'];
        $rezdy_total_paid = $response['booking']['totalPaid'];
        $rezdy_due_amount = $response['booking']['totalDue'];
        $rezdy_created_date = $response['booking']['dateCreated'];
        $rezdy_confirmed_date = $response['booking']['dateConfirmed'];
        $attemps = $response['booking']['status'];
        $rezdy_response_params = json_encode($response['booking']['items']);
        $types = [];

        foreach ($response['booking']['payments'] as $paymentsRow) {
            $types[] = $paymentsRow['type'];
        }

        $rezdy_payment_type = implode(", ", $types);
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'rezdy_order_id' => $rezdy_order_id,
            "rezdy_response_params" => "$rezdy_response_params",
            "rezdy_booking_status" => $rezdy_booking_status,
            "rezdy_total_amount" => $rezdy_total_amount,
            "rezdy_total_paid" => $rezdy_total_paid,
            "rezdy_due_amount" => $rezdy_due_amount,
            "rezdy_payment_type" => $rezdy_payment_type,
            "rezdy_created_date" => $rezdy_created_date,
            "rezdy_confirmed_date" => $rezdy_confirmed_date
        );

        $where = array(
            'id' => $transaction_id,
            'transactionID' => $transaction_int_id,
        );
        $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Rezdy booking status: " . $attemps . PHP_EOL . "User name: " . data_get($this->transaction, 'username', '') . PHP_EOL . "User email: " . data_get($this->transaction, 'useremail', '') . PHP_EOL .  "Table inserted_id: " . $transaction_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $log_dir = PLUGIN_DIR_PATH . 'payment_logs/klarna_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);

        $this->delete_session_data();

        // Trigger tapfiliate
        $this->tapfiliate_trigger($transaction_id, $rezdy_total_amount, 'EUR', data_get($this->transaction, 'username', ''), data_get($this->transaction, 'useremail', ''));
    }

    protected function delete_session_data()
    {
        global $wpdb;

        // delete session data if session id exists
        if (isset($_SESSION['rezdy_session_id']) && ! empty($_SESSION['rezdy_session_id'])) {
            $rezdy_session_id = $_SESSION['rezdy_session_id'];
            $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
            $where = array(
                'sessionID' => $rezdy_session_id,
            );
            $wpdb->delete($table_add_to_cart_data, $where);
        }
    }

    public function tapfiliate_trigger($inserted_id, $amount, $currency, $userName, $email)
    {
        if (isset($_COOKIE['tapfiliate_referral_code'])) {
            $tapfiliate_api_key = get_option('cc_tapfiliate_api_key');
            if ($tapfiliate_api_key) {

                ##====Run Tapfiliate Click API====##
                $tapfiliate_referral_code = esc_html($_COOKIE['tapfiliate_referral_code']);
                $baseUrl = 'https://api.tapfiliate.com/1.6';
                $apiUrl = $baseUrl . "/clicks/";
                $post_data = '{
                    "referral_code": "' . $tapfiliate_referral_code . '"
                }';
                $request_type = 'POST';
                $headers = [];
                $headers[] = 'Content-Type: application/json';
                $headers[] =  'X-Api-Key: ' . $tapfiliate_api_key;

                $clickResponse = $this->tapfiliate_CURL($apiUrl, $request_type, $post_data, $headers);
                $clickResponseArray = json_decode($clickResponse, true);
                if (isset($clickResponseArray['id'])) {
                    $click_id = $clickResponseArray['id'];
                    $is_conversion = false;
                    $attemps = 'Clicked ID received';
                    $this->tapfiliate_trigger_log_and_db($inserted_id, $is_conversion, $tapfiliate_referral_code, $click_id, $tapfiliate_conversion_id = '', $tapfiliate_external_id = '', $attemps, $userName, $email);

                    ##====Run Tapfiliate Conversion API====##
                    $external_id = $this->generateRandomString();
                    $apiUrl = $baseUrl . "/conversions/";
                    $post_data = '{
                        "click_id": "' . $click_id . '",
                        "external_id": "' . $external_id . '",
                        "amount": ' . $amount . ',
                        "currency": "' . $currency . '",
                        "meta_data": {
                            "order_id": ' . $inserted_id . ',
                            "website_url": "' . home_url() . '"
                        }
                    }';
                    $request_type = 'POST';
                    $headers = [];
                    $headers[] = 'Content-Type: application/json';
                    $headers[] =  'X-Api-Key: ' . $tapfiliate_api_key;

                    $conversionResponse = $this->tapfiliate_CURL($apiUrl, $request_type, $post_data, $headers);
                    $conversionResponseArray = json_decode($conversionResponse, true);
                    if (isset($conversionResponseArray['id'])) {
                        $tapfiliate_conversion_id = $conversionResponseArray['id'];
                        $inserted_id = $conversionResponseArray['meta_data']['order_id'];
                        $is_conversion = true;
                        $attemps = 'Conversion ID received';
                        $this->tapfiliate_trigger_log_and_db($inserted_id, $is_conversion, $tapfiliate_referral_code, $click_id, $tapfiliate_conversion_id, $external_id, $attemps, $userName, $email);
                    }
                }
            }
        }
    }
    public function tapfiliate_CURL($apiUrl, $request_type, $post_data, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function tapfiliate_trigger_log_and_db($inserted_id, $is_conversion, $tapfiliate_referral_code, $click_id, $tapfiliate_conversion_id, $tapfiliate_external_id, $attemps, $userName, $email) ##For Tapfiliate
    {
        global $wpdb;

        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

        if (! $is_conversion) {
            $data_to_update = array(
                'tapfiliate_ref_code' => $tapfiliate_referral_code,
                "tapfiliate_click_id" => $click_id,
            );
        } else {
            $data_to_update = array(
                'tapfiliate_conversion_id' => $tapfiliate_conversion_id,
                "tapfiliate_external_id" => $tapfiliate_external_id,
            );
        }

        $where = array( 'id' => $inserted_id );
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Tapfiliate status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $email . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $log_dir = PLUGIN_DIR_PATH . 'payment_logs/klarna_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    protected function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function should_display_klarna_option($products)
    {
        $enabled = [];
        $origin = get_option('cc_picked_color') ?? 'cdt';
        $originKlarnaLists = read_local_data('product_klarna.json');

        foreach ($products as $product) {
            $code = data_get($product, 'productCode');

            if ($code) {
                foreach ($originKlarnaLists as $originKlarnaList) {
                    if ($originKlarnaList['origin'] == $origin) {
                        $lists = $originKlarnaList['lists'];

                        foreach ($lists as $list) {
                            $tours = $list['tours'];
                            $is_enabled = data_get($list, 'klarna_enabled', true);

                            if (in_array($code, $tours)) {
                                $enabled[] = $is_enabled;
                            }
                        }
                    }
                    }
            }
        }
		
		return false;

        return ! in_array(false, $enabled);
    }
}
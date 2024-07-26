<?php defined('ABSPATH') || exit; ?>

<?php

use CC_RezdyAPI\Rezdy\Util\Config;


$token = isset($_GET['token']) ? $_GET['token'] : '';
$PayerID = isset($_GET['PayerID']) ? $_GET['PayerID'] : '';
global $wpdb;
session_start();


if ($token) {
    //Capture order v2 url
    try {

        #Get
        $cookie_name = "CUSTOMSESSIONID";
        $session_id = $_COOKIE[$cookie_name];

        $_ARRAY_SESSION = array();
        $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_add_to_cart_data WHERE sessionID = %s",
            $session_id
        );
        $results = $wpdb->get_results($query);
        if ($results && count($results) === 1) {
            $row = $results[0];
            $_ARRAY_SESSION[] = json_decode($row->sessionData, true);
        }




        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $paypal_client_id = get_option('cc_paypal_client_id');
        $paypal_secret_api_key = get_option('cc_paypal_secret_api_key');
        $paypal_live = get_option('cc_paypal_live');
        $baseUrl = ($paypal_live == 'yes') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $apiUrl = "$baseUrl/v2/checkout/orders/$token/capture";
        $request_type = 'POST';
        $auth = 'Basic ' . base64_encode($paypal_client_id . ':' . $paypal_secret_api_key);
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Prefer: return=representation';
        //$headers[] = 'Paypal-Mock-Response: {"mock_application_codes": "PAYEE_BLOCKED_TRANSACTION"}';
        $headers[] =  'Authorization: ' . $auth;


        $order_result = paypal_request($apiUrl, $request_type, $headers);

        $order_response = json_decode($order_result, true);

        if (isset($order_response['name'])) {
            throw new Exception($order_response['name'] . ': ' . $order_response['message']);
        }


        $orderID = $order_response['id'];
        $order_intent = $order_response['intent'];
        $order_status = $order_response['status'];
        $transaction_status = $order_response['purchase_units'][0]['payments']['captures'][0]['status'];
        $transactionID = $order_response['purchase_units'][0]['payments']['captures'][0]['id'];
        $amount_captured = $order_response['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
        $currency = $order_response['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];

        if ($transaction_status == 'COMPLETED') {
            $success_message = $transaction_status;
            $failure_message = '';
            $statusInFlag = 1;


            $attemps = $transaction_status;
        } else {
            $success_message = '';
            $failure_message = $transaction_status;
            $statusInFlag = 2;


            $attemps = $transaction_status;
        }



        updateOrder($success_message, $failure_message, $rezdy_order_id = '', $transactionID, $order_status = $statusInFlag, $totalPaid = $amount_captured, $attemps, $plugin_dir, $token, $PayerID, $_ARRAY_SESSION);



        ##====Create Booking in Rezdy====##
        $itemParams = $_ARRAY_SESSION[0]['rezdyparams'];
        $itemParams['payments'][0]['label'] = "Paypal Payment transaction Id: " . $transactionID . " and order coming from " . home_url();

        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.booking_create');
        $rezdy_api_key = get_option('cc_rezdy_api_key');
        $apiUrl = $baseUrl;
        $request_type = 'POST';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($itemParams));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Apikey: ' . $rezdy_api_key;
        $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);
        $resultArray = json_decode($result, true);
        $rezdy_order_id = $resultArray['booking']['orderNumber'];
        $rezdy_booking_status = $resultArray['booking']['status'];
        $rezdy_total_amount = $resultArray['booking']['totalAmount'];
        $rezdy_total_paid = $resultArray['booking']['totalPaid'];
        $rezdy_due_amount = $resultArray['booking']['totalDue'];
        $rezdy_created_date = $resultArray['booking']['dateCreated'];
        $rezdy_confirmed_date = $resultArray['booking']['dateConfirmed'];
        $attemps = $resultArray['booking']['status'];
        $rezdy_response_params = json_encode($resultArray['booking']['items']);

        $types = [];
        foreach ($resultArray['booking']['payments'] as $paymentsRow) {
            $types[] = $paymentsRow['type'];
        }
        $rezdy_payment_type = implode(", ", $types);

        updateRezdyOrder($rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $_ARRAY_SESSION);




        ##Delete session data
        $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
        $where = array(
            'sessionID' => $session_id,
        );
        $wpdb->delete($table_add_to_cart_data, $where);

        session_destroy();


        $success_url = get_option('cc_success_url');
        if ($success_url != '') {

            $redirectionURL = $success_url . "?transactionID=" . $transactionID;
            wp_redirect($redirectionURL, 301);
            exit();
        } else {
            $redirectionURL = home_url() . "/success?transactionID=" . $transactionID;
            wp_redirect($redirectionURL, 301);
            exit();
        }
    } catch (Exception $e) {

        //echo 'Error: ' . $e->getMessage();
        $attemps = 'Error: ' . $e->getMessage();
        updateOrder($success_message = '', $failure_message = $e->getMessage(), $rezdy_order_id = '', $transactionID = '', $order_status = 2, $totalPaid = '', $attemps, $plugin_dir, $token, $PayerID, $_ARRAY_SESSION);

        $cancel_url = get_option('cc_cancel_url');
        if ($cancel_url != '') {

            $redirectionURL = $cancel_url;
            wp_redirect($redirectionURL, 301);
            exit();
        } else {
            $redirectionURL = home_url() . "cancel/" . $_ARRAY_SESSION[0]['inserted_id'];
            wp_redirect($redirectionURL, 301);
            exit();
        }
    }
}




function paypal_request($apiUrl, $request_type, $headers)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return curl_error($ch);
    } else {
        return $result;
    }
    curl_close($ch);
}

function updateOrder($success_message, $failure_message, $rezdy_order_id, $transactionID, $order_status, $totalPaid, $attemps, $plugin_dir, $token, $PayerID, $_ARRAY_SESSION)
{

    global $wpdb;
    ##Update order
    $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
    $data_to_update = array(
        'success_message' => $success_message,
        'failure_message' => $failure_message,
        'rezdy_order_id' => $rezdy_order_id,
        'transactionID' => $transactionID,
        'order_status' => $order_status,
        'response_time' => current_time('mysql'),
        'totalPaid' => $totalPaid,
        "paypal_token" => $token,
        "paypal_payer_id" => $PayerID,
    );

    // Define the WHERE clause to identify the row to update
    $where = array(
        'id' => $_ARRAY_SESSION[0]['inserted_id'], // Assuming rezdy_order_id is the unique identifier
    );

    // Perform the update
    $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


    ##log file update
    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Payment status: " . $attemps . PHP_EOL . "User name: " . $_ARRAY_SESSION[0]['paymentObject']['username'] . PHP_EOL . "User email: " . $_ARRAY_SESSION[0]['paymentObject']['useremail'] . PHP_EOL .  "Table inserted_id: " . $_ARRAY_SESSION[0]['inserted_id'] . PHP_EOL . "-------------------------" . PHP_EOL;


    $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
    file_put_contents($fileName, $log, FILE_APPEND);
}

function updateRezdyOrder($rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $_ARRAY_SESSION)
{

    global $wpdb;
    ##Update order
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

    // Define the WHERE clause to identify the row to update
    $where = array(
        'id' => $_ARRAY_SESSION[0]['inserted_id'], // Assuming rezdy_order_id is the unique identifier
    );

    // Perform the update
    $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


    ##log file update
    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Rezdy booking status: " . $attemps . PHP_EOL . "User name: " . $_ARRAY_SESSION[0]['paymentObject']['username'] . PHP_EOL . "User email: " . $_ARRAY_SESSION[0]['paymentObject']['useremail'] . PHP_EOL .  "Table inserted_id: " . $_ARRAY_SESSION[0]['inserted_id'] . PHP_EOL . "-------------------------" . PHP_EOL;


    $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
    file_put_contents($fileName, $log, FILE_APPEND);
}

?>
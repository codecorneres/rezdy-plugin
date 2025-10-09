<?php
##IPN_HUB

$raw_post_data = file_get_contents('php://input');
file_put_contents("notify_url_data.txt", $raw_post_data);

//$raw_post_data = file_get_contents('notify_url_data.txt');


$raw_post_array = explode('&', $raw_post_data);

$myPost = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
}
// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
if (function_exists('get_magic_quotes_gpc')) {
    $get_magic_quotes_exists = true;
}
foreach ($myPost as $key => $value) {
    if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
    } else {
        $value = urlencode($value);
    }
    $req .= "&$key=$value";
}


$paypal_live = get_option('cc_paypal_live');
$validateUrl = ($paypal_live == 'yes') ? 'https://ipnpb.paypal.com' : 'https://ipnpb.sandbox.paypal.com';

// Step 2: POST IPN data back to PayPal to validate
$ch = curl_init($validateUrl);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
if (!($res = curl_exec($ch))) {
    // error_log("Got " . curl_error($ch) . " when processing IPN data");
    curl_close($ch);
    exit;
}

if (strcmp($res, "VERIFIED") == 0) {
    // The IPN is verified, process it:
    // check whether the payment_status is Completed
    // check that txn_id has not been previously processed
    // check that receiver_email is your Primary PayPal email
    // check that payment_amount/payment_currency are correct
    // process the notification
    // assign posted variables to local variables
    $item_name = $myPost['item_name'];
    $item_number = $myPost['item_number'];
    $payment_status = $myPost['payment_status'];
    $payment_amount = $myPost['mc_gross'];
    $payment_currency = $myPost['mc_currency'];
    $txn_id = $myPost['txn_id'];
    $receiver_email = $myPost['receiver_email'];
    $payer_email = $myPost['payer_email'];
    $transaction_subject = $myPost['transaction_subject'];
    $userName = $myPost['first_name'] .  ' ' . $myPost['last_name'];

    if ($payment_status == 'Completed') {

        preg_match('/(http[s]?:\/\/[^\s]+)/', $transaction_subject, $url_match);
        preg_match('/record Id is (\d+)/', $transaction_subject, $id_match);
        if (!empty($url_match[1]) && !empty($id_match[1])) {
            $website_url = $url_match[1];
            $inserted_id = $id_match[1];

            // echo "Website URL: " . $website_url . PHP_EOL;
            // echo '<br />';
            // echo "Record ID: " . $inserted_id . PHP_EOL;
            // echo '<br />';
            // echo '<br />';
            // echo '<br />';
            // echo '<br />';



            if (rtrim($website_url, '/') === rtrim('https://codecorners.in/demo/rezdy-demo/', '/')) {
                $baseWebsiteURL = 'https://codecorners.in/demo/rezdy-demo/';
            } elseif (rtrim($website_url, '/') === rtrim('https://staging.carpediemtours.com/', '/')) {
                $baseWebsiteURL = 'https://staging.carpediemtours.com/';
            } else {
                echo "Invalid Website URL!";
                exit;
            }

            $response = notify_data_process($baseWebsiteURL, $req);
            if ($response) {
                $attemps = 'Notify return Data successfully processed to ' . $baseWebsiteURL;
                notify_logs($attemps, $userName, $payer_email, $inserted_id);
            }
            curl_close($ch);
        } else {
            $attemps = 'URL or Record ID not found!';
            $inserted_id = 'Not Found';
            notify_logs($attemps, $userName, $payer_email, $inserted_id);
        }
    }
} else if (strcmp($res, "INVALID") == 0) {
    // IPN invalid, log for manual investigation
    $attemps = "The response from IPN was: <b>" . $res . "</b>";
    $userName = '';
    notify_logs($attemps, $userName, $payer_email, $inserted_id);
}
curl_close($ch);



function notify_data_process($baseWebsiteURL, $req)
{
    $url =  $baseWebsiteURL . 'notify_data/';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    return $response;
}

function notify_logs($attemps, $userName, $payer_email, $inserted_id)
{

    ##log file update
    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Notify return status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $payer_email . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;

    $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
    $log_dir = $plugin_dir . 'src/payment_logs/paypal_notify_return_logs/';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
    file_put_contents($fileName, $log, FILE_APPEND);
}

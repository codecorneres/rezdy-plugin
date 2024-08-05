<?php

namespace CC_RezdyAPI\Frontend\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Booking;
use CC_RezdyAPI\Rezdy\Requests\Customer;
use CC_RezdyAPI\Rezdy\Requests\Objects\BookingItem;
use CC_RezdyAPI\Rezdy\Requests\Objects\BookingItemQuantity;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use DateTime;
use CC_RezdyAPI\Rezdy\Util\Config;

class BookingDetails extends Screen
{


    public function render()
    {
        global $wpdb;

        $_ARRAY_SESSION = array();


        $cookie_name = "CUSTOMSESSIONID";
        $session_id = $_COOKIE[$cookie_name];


        $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
        $query = $wpdb->prepare(
            "SELECT * FROM $table_add_to_cart_data WHERE sessionID = %s",
            $session_id
        );
        $results = $wpdb->get_results($query);
        if ($results && count($results) === 1) {
            $row = $results[0];
            $_ARRAY_SESSION[] = json_decode($row->sessionData, true);
        } elseif ($results && count($results) > 1) {
            foreach ($results as $result) {
                // Delete extra records
                $wpdb->delete(
                    $table_add_to_cart_data,
                    ['id' => $result->id],
                    ['%d']
                );
            }
        } else {
            //Insert
            $data_sessionID = array(
                'sessionID' => $session_id
            );
            $wpdb->insert($table_add_to_cart_data, $data_sessionID);
        }




        if (!empty($_POST)) {

            // echo '<pre>';
            // print_r($_POST);
            // die('here');


            $postData = $_POST;

            ##Update postData
            $post_data_to_update = array(
                'postData' => json_encode($_POST)
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $post_data_to_update, $where);


            $guzzleClient           = new RezdyAPI(get_option('cc_rezdy_api_key'));
            $rezdy_api_product_code = $postData['OrderItem']['productCode'];
            $product                = $guzzleClient->products->get($rezdy_api_product_code);

            $select_date = date('Y-m-d', strtotime($postData['OrderItem']['preferredDate']));
            $TodayDate = date('Y-m-d', time());
            if ($TodayDate == $select_date) {
                $selected_date = date('Y-m-d H:m:s', strtotime($postData['OrderItem']['preferredDate'] . ' ' . date('H:i:s')));
            } else {
                $selected_date = date('Y-m-d 00:00:00', strtotime($postData['OrderItem']['preferredDate']));
            }
            $lastDate = date("Y-m-t", strtotime($selected_date));
            $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));
            $availabilitySearch = new SessionSearch([
                'productCode' => $postData['OrderItem']['productCode'],
                'startTimeLocal' => $selected_date,
                'endTimeLocal' => $lastDateTime,
                'limit'             => 500
            ]);
            $availabilities = $guzzleClient->availability->search($availabilitySearch);

            $response = array();

            foreach ($availabilities->sessions as $key => $availability) {


                if ($availability->id == $postData['schedule_time']) {

                    $product = $guzzleClient->products->get($availability->productCode);

                    $selected_date = date('Y-m-d', strtotime($postData['OrderItem']['preferredDate']));
                    $startTimeLocal = date('Y-m-d', strtotime($availability->startTimeLocal));
                    $sessionDate = date('Y-m-d H:i', strtotime($availability->startTimeLocal));
                    if ($selected_date == $startTimeLocal) {
                        $sessionTotalPrice = 0;
                        $priceOptions = [];
                        $totalPrice = 0;
                        $quantity = 0;
                        $totalQuantity = 0;

                        foreach ($availability->priceOptions as $key => $option) {

                            foreach ($postData['ItemQuantity'][$postData['OrderItem']['productCode']] as $IndexNew => $priceOptionNew) {
                                if ($priceOptionNew['priceOption']['label'] == $option->label) {

                                    $quantity = $priceOptionNew['quantity'];
                                    $priceOptionID = $priceOptionNew['priceOption']['id'];
                                    $price = $option->price;
                                    $label = $option->label;
                                    if (isset($option->priceGroupType)) {
                                        $found = $this->getGroupValue($quantity, $option->label);
                                        if ($found) {
                                            $sessionTotalPrice = $price;
                                            $totalPrice += $sessionTotalPrice;
                                        }
                                    } else {
                                        $sessionTotalPrice = $quantity * $price;
                                        $totalPrice += $sessionTotalPrice;
                                    }


                                    $totalQuantity += $quantity;

                                    if (isset($option->priceGroupType)) {
                                        $priceOptions[] = [
                                            "label"        => $label,
                                            "quantity"     => $quantity,
                                            "price"        => number_format($price, 2, '.', ''),
                                            'sessionTotalPrice' => number_format($sessionTotalPrice, 2, '.', ''),
                                            'priceOptionID' => $priceOptionID,
                                        ];
                                    } else {

                                        if ($quantity > 0) {
                                            $priceOptions[] = [
                                                "label"        => $label,
                                                "quantity"     => $quantity,
                                                "price"        => number_format($price, 2, '.', ''),
                                                'sessionTotalPrice' => number_format($sessionTotalPrice, 2, '.', ''),
                                                'priceOptionID' => $priceOptionID,
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $dateTime = new DateTime($sessionDate);
                        $formattedDate = $dateTime->format('j M Y H:i');
                        $response[] = [
                            "name"                  => $product->product->name,
                            "productCode"           => $product->product->productCode,
                            'quantityRequiredMin'   => $product->product->quantityRequiredMin,
                            'quantityRequiredMax'   => $product->product->quantityRequiredMax,
                            "sessionDate"           => $formattedDate,
                            "priceOptions"          => $priceOptions,
                            "totalPrice"            => number_format($totalPrice, 2, '.', ''),
                            "totalQuantity"         => $totalQuantity,
                            "schedule_time"         => $postData['schedule_time'],
                            "tour_url"              => $postData['tour_url']
                        ];
                    }
                }
            }


            // echo '<pre>';
            // print_r($response);
            // exit();




            if (isset($_ARRAY_SESSION[0][$session_id])) {
                $found = false;
                foreach ($_ARRAY_SESSION[0][$session_id] as $key => $sessionData) :
                    if ($sessionData['schedule_time'] == $response[0]['schedule_time']) {
                        $_ARRAY_SESSION[0][$session_id][$key] = $response[0];
                        $found = true;
                        break;
                    }
                endforeach;

                if (!$found) {
                    $_ARRAY_SESSION[0][$session_id][] = $response[0];
                }
            } else {
                $_ARRAY_SESSION[0][$session_id] = $response;
            }



            ##Update sessionData
            $session_data_to_update = array(
                'sessionData' => json_encode($_ARRAY_SESSION[0])
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);



            $this->renderTemplate('booking-details.php', [
                'product' => $product,
                'session' => $postData,
                'availabilities' => $availabilities->sessions,
                'response' => $_ARRAY_SESSION[0][$session_id],
                'quantity' => $quantity,
                'session_id' => $session_id
            ]);
        } else {

            if (!empty($_ARRAY_SESSION[0][$session_id])) {
                foreach ($_ARRAY_SESSION[0][$session_id] as $keyyy => $valueee) {
                    if ($valueee['totalPrice'] == 0 && $valueee['totalQuantity'] == 0) {
                        unset($_ARRAY_SESSION[0][$session_id][$keyyy]);
                    }
                }

                ##Update sessionData
                $session_data_to_update = array(
                    'sessionData' => json_encode($_ARRAY_SESSION[0])
                );
                $where = array(
                    'sessionID' => $session_id,
                );
                $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);
            }

            $this->renderTemplate('booking-details.php', [
                'response' => $_ARRAY_SESSION[0][$session_id],
                'session_id' => $session_id
            ]);
        }
    }


    public function scripts()
    {
        //

        global $wp_query;
        // echo '<pre>';
        // print_r($post);
        // echo $post->post_name;
        // exit();
        if ($wp_query->query_vars['pagenamecustom'] == 'checkout') {
            $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
            wp_enqueue_style('cc-rezdy-api', "{$base}src/assets/includes/css/booking-details-style.css", [], $this->appContext::SCRIPTS_VERSION);

            wp_enqueue_script('toggle-jquery', $base . 'src/assets/includes/js/jquery-2.2.4.min.js', [], $this->appContext::SCRIPTS_VERSION);
            wp_enqueue_script('booking-toggle-js', $base . 'src/assets/includes/js/booking-details.js', [], $this->appContext::SCRIPTS_VERSION);
            wp_localize_script('booking-ajax-url', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    function quote_booking_checkout_callback()
    {

        // echo '<pre>';
        // print_r($_POST);
        // exit();
        global $wpdb;
        $_ARRAY_SESSION = array();
        $session_id = $_POST['rezdy_session_id'];
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



        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['p_v_code'])) {


            //Get Voucher
            $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.get_voucher') . urlencode($_POST['p_v_code']);
            $rezdy_api_key = get_option('cc_rezdy_api_key');
            $apiUrl = $baseUrl;
            $request_type = 'GET';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$apiUrl");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Apikey: ' . $rezdy_api_key;
            $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $voucher_result = curl_exec($ch);
            curl_close($ch);
            $voucherArray = json_decode($voucher_result, true);
            if ($voucherArray['requestStatus']['success'] == true) {
                $valueType = $voucherArray['voucher']['valueType'];
                $voucher_value = $voucherArray['voucher']['value'];

                // if($valueType == 'PERCENT' || $valueType == 'PERCENT_LIMITPRODUCT'){
                //     $codeType = 'coupon';
                // }else{
                //     $codeType = 'voucher';
                // }

            } elseif ($voucherArray['requestStatus']['success'] == false) {
                $errorMessage = $voucherArray['requestStatus']['error']['errorMessage'];
                $errorMessage = ($errorMessage) ? $errorMessage : 'Invalid Input';
                wp_send_json(array('requestStatus' => false, 'position' => 'getvoucher', 'error' => $errorMessage));
            } else {
                wp_send_json(array('error' => 'something went wrong!!', 'position' => 'getvoucher'));
            }

            //Booking params
            $customerParams = [
                'firstName' => $_POST['fname'],
                'lastName' => $_POST['lname'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone']
            ];

            $itemParams = [];
            $out_counter = 0;
            foreach ($_POST['order'] as $key => $order) {
                if ($order['product_code'] && $order['sessionDate']) {
                    $in_counter = 0;
                    foreach ($_POST['priceOptions'][$key] as $i => $option) {

                        $itemParams['items'][$out_counter]['productCode'] = $order['product_code'];
                        $itemParams['items'][$out_counter]['startTimeLocal'] = date('Y-m-d H:i:s', strtotime($order['sessionDate']));
                        $itemParams['items'][$out_counter]['quantities'][$in_counter]['optionLabel'] = $option['optionLabel'];
                        $itemParams['items'][$out_counter]['quantities'][$in_counter]['value'] = $option['value'];
                        $in_counter++;
                    }
                    $in_in_counter = 0;
                    foreach ($_POST['participant'][$key] as $p => $participant) {
                        $pr = 0;
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['label'] = 'First Name';
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['value'] = $participant['first_name'];
                        $pr++;
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['label'] = 'Last Name';
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['value'] = $participant['last_name'];
                        $in_in_counter++;
                    }
                }

                $out_counter++;
            }

            $itemParams['customer'] = $customerParams;

            $itemParams['vouchers'] = [];
            if (isset($_POST['applied_voucher_codes']) && !empty($_POST['applied_voucher_codes'])) {
                foreach ($_POST['applied_voucher_codes'] as $kk => $code) :
                    array_push($itemParams['vouchers'], $code['codeName']);
                endforeach;
            }
            array_push($itemParams['vouchers'], $_POST['p_v_code']);


            ##====Quote Booking in Rezdy====##
            $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.booking_quote');
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
            // echo '<pre>';
            // print_r($resultArray);
            // print_r($_POST);
            // exit();

            if ($resultArray['requestStatus']['success'] == true) {
                $totalDue       = $resultArray['booking']['totalDue'];
                $totalPaid      = $resultArray['booking']['totalPaid'];
                $totalAmount    = $resultArray['booking']['totalAmount'];

                $codeType = '';
                $remaining = '';
                $Havecoupons = false;
                $_Paid = 0;
                if (isset($resultArray['booking']['vouchers'])) {
                    $vouchers = $resultArray['booking']['vouchers'];
                }
                if ($resultArray['booking']['coupon']) {
                    $coupon = $resultArray['booking']['coupon'];
                }

                if (isset($_POST['applied_voucher_codes']) && !empty($_POST['applied_voucher_codes'])) {
                    foreach ($_ARRAY_SESSION[0]['voucherCode']['codes'] as $voucherIndex => $voucherRowData) :
                        $_Paid += $voucherRowData['totalPaid'];
                    endforeach;
                }

                if (isset($_POST['applied_coupon_code']) && !empty($_POST['applied_coupon_code'])) {
                    $Havecoupons = true;
                }


                if (!empty($vouchers)) {
                    foreach ($vouchers as $voucherData) :
                        if ($voucherData == $_POST['p_v_code']) {
                            $codeType = 'voucher';

                            if ($totalDue < 0 || $totalDue == 0) {
                                $totalDue = 0;
                                $totalPaid = $_POST['priceValue'];
                                $_Paid += $totalPaid;
                                $_ARRAY_SESSION[0]['voucherCode']['codes'][$_POST['p_v_code']]['totalPaid'] = $_POST['priceValue'];
                                if ($voucher_value && ($voucher_value  != $_POST['priceValue'])) {
                                    $remaining = $voucher_value - $_POST['priceValue'];
                                    $_ARRAY_SESSION[0]['voucherCode']['codes'][$_POST['p_v_code']]['remaining'] = $remaining;
                                }
                            } else {

                                $totalPaid = ($voucher_value) ? $voucher_value : $totalPaid;
                                $_Paid += $totalPaid;
                                $_ARRAY_SESSION[0]['voucherCode']['codes'][$_POST['p_v_code']]['totalPaid'] = $totalPaid;
                            }
                        }
                    endforeach;

                    if ($Havecoupons == true) {
                        //$_POST['applied_coupon_code']['codeName']
                        //$_POST['applied_coupon_code']['codePrice']

                        $apiUrl = Config::get('endpoints.base_url') . 'vouchers/' . $_POST['applied_coupon_code']['codeName'];
                        $request_type = 'GET';
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "$apiUrl");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        $couponresult = curl_exec($ch);
                        curl_close($ch);
                        $couponArray = json_decode($couponresult, true);

                        $totalAmouontForCoupon = $totalAmount - $_Paid;
                        if ($couponArray['requestStatus']['success'] == true) {
                            if ($couponArray['voucher']['valueType'] == 'PERCENT' || $couponArray['voucher']['valueType'] == 'PERCENT_LIMITPRODUCT') {
                                $amountToUpdate = ($couponArray['voucher']['value'] / 100) * $totalAmouontForCoupon;
                                $_ARRAY_SESSION[0]['couponCode']['code'][$couponArray['voucher']['code']]['totalPaid'] = $amountToUpdate;
                            }
                        }
                    }
                }




                if (!empty($coupon)) {

                    if ($coupon == $_POST['p_v_code']) {
                        $codeType = 'coupon';
                        if ($Havecoupons == true) {
                            $errorMessage = "You can only use one promo code per order. $coupon cannot be used., $coupon is not valid";
                            wp_send_json(array('requestStatus' => false, 'codeType' => $codeType, 'error' => $errorMessage));
                        } else {

                            if ($totalDue < 0 || $totalDue == 0) {
                                $totalDue = 0;
                                $totalPaid = $_POST['priceValue'];
                                $_Paid += $totalPaid;
                                $_ARRAY_SESSION[0]['couponCode']['code'][$_POST['p_v_code']]['totalPaid'] = $_POST['priceValue'];
                            } else {

                                $totalPaid = ($valueType == 'PERCENT' || $valueType == 'PERCENT_LIMITPRODUCT') ? ($voucher_value / 100) * $_POST['priceValue'] : $voucher_value;
                                $_Paid += $totalPaid;

                                $_ARRAY_SESSION[0]['couponCode']['code'][$_POST['p_v_code']]['totalPaid'] = $totalPaid;
                            }
                        }
                    }
                }

                if (isset($_POST['applied_coupon_code']) && !empty($_POST['applied_coupon_code'])) {
                    foreach ($_ARRAY_SESSION[0]['couponCode']['code'] as $codeIndex => $codeRowData) :
                        $_Paid += $codeRowData['totalPaid'];
                    endforeach;
                }



                $alltotalPaid = $_Paid;
                $alltotalDue = $totalAmount - $_Paid;

                $_ARRAY_SESSION[0]['codeData']['alltotalDue'] = $alltotalDue;
                $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] = $alltotalPaid;

                $productCount = count($_ARRAY_SESSION[0][$session_id]);
                $_ARRAY_SESSION[0]['codeData']['productCount'] = $productCount;

                if (isset($codeType) && isset($alltotalDue) && isset($alltotalPaid) && isset($totalPaid)) {

                    ##Update sessionData
                    $session_data_to_update = array(
                        'sessionData' => json_encode($_ARRAY_SESSION[0])
                    );
                    $where = array(
                        'sessionID' => $session_id,
                    );
                    $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);




                    wp_send_json(array('requestStatus' => true, 'codeType' => $codeType, 'alltotalDue' => $alltotalDue, 'alltotalPaid' => $alltotalPaid, 'totalPaid' => $totalPaid, 'remaining' => $remaining, 'amountToUpdate' => $amountToUpdate));
                } else {
                    $errorMessage = 'Code is Invalid';
                    wp_send_json(array('requestStatus' => false, 'error' => $errorMessage));
                }
            } elseif ($resultArray['requestStatus']['success'] == false) {
                $errorMessage = $resultArray['requestStatus']['error']['errorMessage'];
                wp_send_json(array('requestStatus' => false, 'error' => $errorMessage));
            } else {
                wp_send_json(array('error' => 'something went wrong!!'));
            }
        }
    }

    function booking_checkout_callback()
    {

        //if (!session_id()) {
        session_start();
        //}

        // echo '<pre>';
        // print_r($_POST);
        // exit();
        // print_r($_POST['selectedcountryCode']);
        // exit();
        //wp_send_json($_POST);
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            global $wpdb;

            //Get Success and Cancel Url
            $success_url = get_option('cc_success_url');
            $cancel_url =  get_option('cc_cancel_url');

            //Booking params
            $customerParams = [
                'firstName' => $_POST['fname'],
                'lastName' => $_POST['lname'],
                'email' => $_POST['email'],
                'phone' => $_POST['selectedcountryCode'] . $_POST['phone']
            ];

            $po_firstName = $_POST['fname'];
            $po_lastName = $_POST['lname'];
            $po_phone = $_POST['selectedcountryCode'] . $_POST['phone'];
            $po_country = strtoupper($_POST['country']);



            $PayPalItem = 0;
            $itemParams = [];
            $items_name = [];
            $itemsPayPal = [];
            $itemsStripe = [];
            $itemsPayPal['intent'] = 'CAPTURE';
            $out_counter = 0;
            foreach ($_POST['order'] as $key => $order) {
                if ($order['product_code'] && $order['sessionDate']) {
                    $in_counter = 0;

                    $items_name[] = $order['product_code'];


                    foreach ($_POST['priceOptions'][$key] as $i => $option) {

                        $optionLabel = ($option['optionLabel'] == 'Quantity') ? 'Everyone' : $option['optionLabel'];

                        $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['name'] = $order['product_code'] . ' ' . $order['sessionDate'] . ' (' . $optionLabel . ') ';
                        $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['quantity'] = $option['value'];
                        $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['unit_amount']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['unit_amount']['value'] = $option['price'];

                        $itemParams['items'][$out_counter]['productCode'] = $order['product_code'];
                        $itemParams['items'][$out_counter]['startTimeLocal'] = date('Y-m-d H:i:s', strtotime($order['sessionDate']));
                        $itemParams['items'][$out_counter]['quantities'][$in_counter]['optionLabel'] = $option['optionLabel'];
                        $itemParams['items'][$out_counter]['quantities'][$in_counter]['value'] = $option['value'];







                        $in_counter++;
                        $PayPalItem++;
                    }
                    $in_in_counter = 0;
                    foreach ($_POST['participant'][$key] as $p => $participant) {
                        $pr = 0;
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['label'] = 'First Name';
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['value'] = $participant['first_name'];
                        $pr++;
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['label'] = 'Last Name';
                        $itemParams['items'][$out_counter]['participants'][$in_in_counter]['fields'][$pr]['value'] = $participant['last_name'];
                        $in_in_counter++;
                    }
                }

                $out_counter++;
            }

            $itemParams['payments'][0]['amount'] = number_format($_POST["priceValue"], 2, '.', '');
            $itemParams['payments'][0]['type'] = $_POST['method'];
            $itemParams['payments'][0]['currency'] = "EUR";
            $itemParams['customer'] = $customerParams;
            $itemParams['comments'] = $_POST['comments'];


            $itemParams['vouchers'] = [];
            if (isset($_POST['applied_voucher_codes']) && !empty($_POST['applied_voucher_codes'])) {
                foreach ($_POST['applied_voucher_codes'] as $kk => $code) :
                    array_push($itemParams['vouchers'], $code['codeName']);
                endforeach;
            }

            if (isset($_POST['applied_coupon_code']) && !empty($_POST['applied_coupon_code'])) {
                $itemParams['coupon'] = $_POST['applied_coupon_code']['codeName'];
            }






            if ($_POST["priceValue"] < 0 || $_POST["priceValue"] == 0) {

                $itemParams['payments'][0]['label'] = "payment is 0, vouchers or promocodes are applied for this order.";
                ##====Create Booking in Rezdy====##
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


                $types = [];
                $payments_amount = 0;
                foreach ($resultArray['booking']['payments'] as $paymentsRow) {
                    $types[] = $paymentsRow['type'];
                    $payments_amount += $paymentsRow['amount'];
                }
                $types_string = implode(", ", $types);


                $string = $this->generateRandomString();
                $transactionID = 'bookingWithCodes' . $string;
                $items = json_encode($resultArray['booking']['items']);

                $items_nameString = implode(", ", $items_name);
                $site = home_url();
                $site_without_http = trim(str_replace(array('http://', 'https://'), '', $site), '/');
                $site_without_domain_extension = preg_replace('/\.[^.\/]+$/i', '', $site_without_http);


                $user_ip = $this->get_user_ip();
                $username = $_POST["fname"] . " " . $_POST["lname"];
                $useremail = $_POST["email"];
                $current_timestamp = current_time('mysql');
                $rezdy_params = json_encode($itemParams);
                $payment_method = $_POST['method'];

                $paymentObject = array("rezdy_order_id" => $rezdy_order_id, "transactionID" => $transactionID, "success_message" => '', "failure_message" => '', "order_status" => 1, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail, "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => number_format($_POST["priceValue"], 2, '.', ''), "payment_method" => $payment_method, "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => $rezdy_booking_status, "rezdy_total_amount" => $rezdy_total_amount, "rezdy_total_paid" => $rezdy_total_paid, "rezdy_due_amount" => $rezdy_due_amount, "rezdy_created_date" => $rezdy_created_date, "rezdy_confirmed_date" => $rezdy_confirmed_date);

                $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

                $data_transactions = array(
                    'rezdy_order_id' => $paymentObject['rezdy_order_id'],
                    'transactionID' => $paymentObject['transactionID'],
                    'success_message' => $paymentObject['success_message'],
                    'failure_message' => $paymentObject['failure_message'],
                    'order_status' => $paymentObject['order_status'],
                    'IP_address' => $paymentObject['IP_address'],
                    'username' => $paymentObject['username'],
                    'useremail' => $paymentObject['useremail'],
                    'firstName' => $paymentObject['firstName'],
                    'lastName' => $paymentObject['lastName'],
                    'phone' => $paymentObject['phone'],
                    'country' => $paymentObject['country'],
                    'date_time' => $paymentObject['date_time'],
                    'response_time' => $paymentObject['response_time'],
                    'totalAmount' => $paymentObject['totalAmount'],
                    'totalPaid' => $paymentObject['totalPaid'],
                    'payment_method' => $paymentObject['payment_method'],
                    "paypal_token" => '',
                    "paypal_payer_id" => '',
                    "rezdy_params" => "$rezdy_params",
                    "rezdy_response_params" => "$items",
                    "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                    "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                    "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                    "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                    "rezdy_payment_type" => $types_string,
                    "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                    "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date']
                );

                $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                $inserted_id = $wpdb->insert_id;
                $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';


                $paymentstatus = 'Order created without payment methods';
                $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;




                $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';

                // Create the directory if it doesn't exist
                if (!file_exists($log_dir)) {
                    mkdir($log_dir, 0755, true); // Recursive directory creation
                }

                $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                file_put_contents($fileName, $log, FILE_APPEND);

                if ($resultArray['requestStatus']['success'] == true) {

                    ##Delete session data
                    $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                    $where = array(
                        'sessionID' => $_POST['rezdy_session_id'],
                    );
                    $wpdb->delete($table_add_to_cart_data, $where);

                    session_destroy();
                    wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                } else {
                    session_destroy();
                    wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                }
            } else {
                if ($_POST['method'] == 'CREDITCARD') {

                    $secret_key = get_option('cc_stripe_secret_api_key');
                    \Stripe\Stripe::setApiKey($secret_key);
                    $token = $_POST['stripeToken'];

                    $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                    $items_nameString = implode(", ", $items_name);
                    $site = home_url();
                    $site_without_http = trim(str_replace(array('http://', 'https://'), '', $site), '/');
                    $site_without_domain_extension = preg_replace('/\.[^.\/]+$/i', '', $site_without_http);


                    $user_ip = $this->get_user_ip();
                    $username = $_POST["fname"] . " " . $_POST["lname"];
                    $useremail = $_POST["email"];
                    $current_timestamp = current_time('mysql');
                    $rezdy_params = json_encode($itemParams);

                    $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => 'STRIPE', "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '');

                    $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

                    $data_transactions = array(
                        'rezdy_order_id' => $paymentObject['rezdy_order_id'],
                        'transactionID' => $paymentObject['transactionID'],
                        'success_message' => $paymentObject['success_message'],
                        'failure_message' => $paymentObject['failure_message'],
                        'order_status' => $paymentObject['order_status'],
                        'IP_address' => $paymentObject['IP_address'],
                        'username' => $paymentObject['username'],
                        'useremail' => $paymentObject['useremail'],
                        'firstName' => $paymentObject['firstName'],
                        'lastName' => $paymentObject['lastName'],
                        'phone' => $paymentObject['phone'],
                        'country' => $paymentObject['country'],
                        'date_time' => $paymentObject['date_time'],
                        'response_time' => $paymentObject['response_time'],
                        'totalAmount' => $paymentObject['totalAmount'],
                        'totalPaid' => $paymentObject['totalPaid'],
                        'payment_method' => $paymentObject['payment_method'],
                        "paypal_token" => '',
                        "paypal_payer_id" => '',
                        "rezdy_params" => "$rezdy_params",
                        "rezdy_response_params" => '',
                        "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                        "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                        "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                        "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                        "rezdy_payment_type" => '',
                        "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                        "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date']
                    );

                    $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                    $inserted_id = $wpdb->insert_id;
                    $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';


                    $paymentstatus = 'Stripe payment started';
                    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                    $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';

                    // Create the directory if it doesn't exist
                    if (!file_exists($log_dir)) {
                        mkdir($log_dir, 0755, true); // Recursive directory creation
                    }

                    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                    file_put_contents($fileName, $log, FILE_APPEND);


                    // Charge the user's card
                    try {
                        $charge = \Stripe\Charge::create(
                            [
                                'amount' => round($_POST['priceValue'] * 100),
                                'currency' => 'eur',
                                'description' => $_POST["comments"],
                                'source' => $token,
                                'metadata' => [
                                    'BillingName' => $_POST["fname"] . " " . $_POST["lname"],
                                    'BillingEmail' => $_POST["email"],
                                    'BillingPhone' => $_POST['selectedcountryCode'] . $_POST["phone"],
                                    'CustomOrderID' => $custom_id
                                ],

                            ],
                            [
                                'idempotency_key' => $this->guidv4(), // Generate a unique idempotency key for each request
                            ]
                        );
                        if ($charge->status == 'succeeded') {

                            $chargeID = $charge->id;
                            $transactionID = $charge->balance_transaction;
                            $amount_captured = $charge->amount_captured;
                            $status = $charge->status;
                            $currency = $charge->currency;

                            $attemps = 'Sripe Payment completed';
                            $totalPaid = round($amount_captured / 100);
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->updateOrder($status, $failure_message = '', $rezdy_order_id = '', $transactionID, $order_status = 1, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                        }
                    } catch (\Stripe\Exception\CardException $e) {
                        // Payment failed, handle card error

                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } catch (\Stripe\Exception\RateLimitException $e) {
                        // Too many requests made to the API too quickly
                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
                        // Invalid parameters were supplied to Stripe's API
                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } catch (\Stripe\Exception\AuthenticationException $e) {
                        // Authentication with Stripe's API failed
                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } catch (\Stripe\Exception\ApiConnectionException $e) {
                        // Network communication with Stripe failed
                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } catch (\Stripe\Exception\ApiErrorException $e) {
                        // Generic error
                        $error = $e->getError()->message;
                        $attemps = 'Stripe payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    }

                    if ($chargeID) {

                        global $wpdb;

                        $itemParams['payments'][0]['label'] = "Stripe Payment transaction Id: " . $transactionID;

                        ##====Create Booking in Rezdy====##
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

                        $attemps = 'Rezdy Booking';
                        $this->updateRezdyOrder($rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);

                        if ($resultArray['requestStatus']['success'] == true) {

                            ##Delete session data
                            $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                            $where = array(
                                'sessionID' => $_POST['rezdy_session_id'],
                            );
                            $wpdb->delete($table_add_to_cart_data, $where);



                            session_destroy();
                            wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                        } else {
                            session_destroy();
                            wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                        }
                    } else {
                        session_destroy();
                        wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => $error));
                    }
                }
                if ($_POST['method'] == 'PAYPAL') {

                    $paypal_client_id = get_option('cc_paypal_client_id');
                    $paypal_secret_api_key = get_option('cc_paypal_secret_api_key');
                    $paypal_live = get_option('cc_paypal_live');
                    $baseUrl = ($paypal_live == 'yes') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';


                    $discount = 0;
                    if (isset($_POST['applied_voucher_codes']) && !empty($_POST['applied_voucher_codes'])) {
                        foreach ($_POST['applied_voucher_codes'] as $kk => $code) :
                            $discount += $code['codePrice'];
                        endforeach;
                    }

                    if (isset($_POST['applied_coupon_code']) && !empty($_POST['applied_coupon_code'])) {
                        $discount += $_POST['applied_coupon_code']['codePrice'];
                    }


                    if ($discount > 0) {
                        $all_totalAmount = $_POST["priceValue"] + $discount;
                        $discountedTotal = $_POST["priceValue"];

                        $itemsPayPal['purchase_units'][0]['amount']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['amount']['value'] = number_format($discountedTotal, 2, '.', '');
                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['item_total']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = number_format($all_totalAmount, 2, '.', '');

                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['discount']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['discount']['value'] = number_format($discount, 2, '.', '');
                    } else {
                        $itemsPayPal['purchase_units'][0]['amount']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['amount']['value'] = number_format($_POST["priceValue"], 2, '.', '');
                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['item_total']['currency_code'] = 'EUR';
                        $itemsPayPal['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = number_format($_POST["priceValue"], 2, '.', '');
                    }

                    $itemsPayPal['application_context']['return_url'] = home_url() . '/return';
                    $itemsPayPal['application_context']['cancel_url'] = get_option('cc_cancel_url');





                    $items_nameString = implode(", ", $items_name);
                    $site = home_url();
                    $site_without_http = trim(str_replace(array('http://', 'https://'), '', $site), '/');
                    $site_without_domain_extension = preg_replace('/\.[^.\/]+$/i', '', $site_without_http);


                    $user_ip = $this->get_user_ip();
                    $username = $_POST["fname"] . " " . $_POST["lname"];
                    $useremail = $_POST["email"];
                    $current_timestamp = current_time('mysql');
                    $rezdy_params = json_encode($itemParams);

                    $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '');

                    $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

                    $data_transactions = array(
                        'rezdy_order_id' => $paymentObject['rezdy_order_id'],
                        'transactionID' => $paymentObject['transactionID'],
                        'success_message' => $paymentObject['success_message'],
                        'failure_message' => $paymentObject['failure_message'],
                        'order_status' => $paymentObject['order_status'],
                        'IP_address' => $paymentObject['IP_address'],
                        'username' => $paymentObject['username'],
                        'useremail' => $paymentObject['useremail'],
                        'firstName' => $paymentObject['firstName'],
                        'lastName' => $paymentObject['lastName'],
                        'phone' => $paymentObject['phone'],
                        'country' => $paymentObject['country'],
                        'date_time' => $paymentObject['date_time'],
                        'response_time' => $paymentObject['response_time'],
                        'totalAmount' => $paymentObject['totalAmount'],
                        'totalPaid' => $paymentObject['totalPaid'],
                        'payment_method' => $paymentObject['payment_method'],
                        "paypal_token" => '',
                        "paypal_payer_id" => '',
                        "rezdy_params" => "$rezdy_params",
                        "rezdy_response_params" => '',
                        "rezdy_booking_status" => '',
                        "rezdy_total_amount" => '',
                        "rezdy_total_paid" => '',
                        "rezdy_due_amount" => '',
                        "rezdy_payment_type" => '',
                        "rezdy_created_date" => '',
                        "rezdy_confirmed_date" => ''
                    );

                    $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                    $inserted_id = $wpdb->insert_id;
                    $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';
                    $itemsPayPal['purchase_units'][0]['items'][0]['description'] = 'Order from website: ' . home_url() . ' and Order table record Id is ' . $inserted_id;
                    $itemsPayPal['purchase_units'][0]['custom_id'] = $custom_id;



                    $paymentstatus = 'Paypal Payment started';
                    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;




                    $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                    $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';

                    // Create the directory if it doesn't exist
                    if (!file_exists($log_dir)) {
                        mkdir($log_dir, 0755, true); // Recursive directory creation
                    }

                    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                    file_put_contents($fileName, $log, FILE_APPEND);



                    //Create order v2 url
                    $apiUrl = "$baseUrl/v2/checkout/orders";
                    $post_data = json_encode($itemsPayPal);

                    $request_type = 'POST';
                    $auth = 'Basic ' . base64_encode($paypal_client_id . ':' . $paypal_secret_api_key);
                    $headers = [];
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'Prefer: return=representation';
                    $headers[] =  'Authorization: ' . $auth;
                    $order_result  = $this->paypal_request($apiUrl, $post_data, $request_type, $headers);
                    $order_response = json_decode($order_result, true);

                    if (!empty($order_response['links'])) {
                        foreach ($order_response['links'] as $order_link) {
                            if ($order_link['rel'] == 'approve') {
                                $approveUrl = $order_link['href'];
                                $session_id = $_POST['rezdy_session_id'];

                                #Get
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

                                $_ARRAY_SESSION[0]['rezdyparams'] = $itemParams;
                                $_ARRAY_SESSION[0]['paymentObject'] = $paymentObject;
                                $_ARRAY_SESSION[0]['inserted_id'] = $inserted_id;


                                ##Update sessionData
                                $session_data_to_update = array(
                                    'sessionData' => json_encode($_ARRAY_SESSION[0])
                                );
                                $where = array(
                                    'sessionID' => $session_id,
                                );
                                $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);
                            }
                        }
                        wp_send_json(array('approveUrl' => $approveUrl, 'success_url' => $success_url));
                    } else {


                        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . 'Error : '  . $order_result . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                        $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';

                        // Create the directory if it doesn't exist
                        if (!file_exists($log_dir)) {
                            mkdir($log_dir, 0755, true); // Recursive directory creation
                        }

                        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                        file_put_contents($fileName, $log, FILE_APPEND);
                        wp_send_json(array('error' => $order_response['error_description']));
                    }
                }
                if ($_POST['method'] == 'AIRWALLEX') {
                    
                    $secret_key =  get_option('cc_airwallex_secret_api_key'); 
                    $client_id = get_option('cc_airwallex_client_id');
                    
                    $token = $_POST['stripeToken'];
                    $status = $_POST['status'];
                    $transactionID = $_POST['intent_id'];


                    $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                    $items_nameString = implode(", ", $items_name);
                    $site = home_url();
                    $site_without_http = trim(str_replace(array('http://', 'https://'), '', $site), '/');
                    $site_without_domain_extension = preg_replace('/\.[^.\/]+$/i', '', $site_without_http);

                    $user_ip = $this->get_user_ip();
                    $username = $_POST["fname"] . " " . $_POST["lname"];
                    $useremail = $_POST["email"];
                    $current_timestamp = current_time('mysql');
                    $rezdy_params = json_encode($itemParams);

                    $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => 'AIRWALLEX', "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '');

                    $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

                    $data_transactions = array(
                        'rezdy_order_id' => $paymentObject['rezdy_order_id'],
                        'transactionID' => $paymentObject['transactionID'],
                        'success_message' => $paymentObject['success_message'],
                        'failure_message' => $paymentObject['failure_message'],
                        'order_status' => $paymentObject['order_status'],
                        'IP_address' => $paymentObject['IP_address'],
                        'username' => $paymentObject['username'],
                        'useremail' => $paymentObject['useremail'],
                        'firstName' => $paymentObject['firstName'],
                        'lastName' => $paymentObject['lastName'],
                        'phone' => $paymentObject['phone'],
                        'country' => $paymentObject['country'],
                        'date_time' => $paymentObject['date_time'],
                        'response_time' => $paymentObject['response_time'],
                        'totalAmount' => $paymentObject['totalAmount'],
                        'totalPaid' => $paymentObject['totalPaid'],
                        'payment_method' => $paymentObject['payment_method'],
                        "paypal_token" => '',
                        "paypal_payer_id" => '',
                        "rezdy_params" => "$rezdy_params",
                        "rezdy_response_params" => '',
                        "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                        "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                        "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                        "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                        "rezdy_payment_type" => "CREDITCARD",
                        "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                        "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date']
                    );

                    $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                    $inserted_id = $wpdb->insert_id;
                    
                    $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';


                    $paymentstatus = 'Airwallex payment started';
                    $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                    $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';

                    // Create the directory if it doesn't exist
                    if (!file_exists($log_dir)) {
                        mkdir($log_dir, 0755, true); // Recursive directory creation
                    }

                    $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                    file_put_contents($fileName, $log, FILE_APPEND);

                    //try {
                    
                    if ( !empty($transactionID)) {

                        $amount_captured = round($_POST['priceValue'] * 100);
                        $status = 'SUCCEEDED';
                        $currency = 'EUR';

                        $attemps = 'Airwallex Payment completed';
                        $totalPaid = round($amount_captured / 100);
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->airwallexUpdateOrder($status, $failure_message = '', $rezdy_order_id = '', $transactionID, $order_status = 1, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    } else {
                        $error = $e->getError()->message;
                        $attemps = 'Airwallex payment failed';
                        $userName = $_POST["fname"] . " " . $_POST["lname"];
                        $this->failedAirwallex($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    }
                    
                    // } catch (Airwallex $e) {
                    //     $error = $e->getError()->message;
                    //     $attemps = 'Airwallex payment failed';
                    //     $userName = $_POST["fname"] . " " . $_POST["lname"];
                    //     $this->failedAirwallex($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                    //  }

                    if ($transactionID) {

                        global $wpdb;

                        $itemParams['payments'][0]['label'] = "Airwallex Payment Intent Id: " . $transactionID;

                        ##====Create Booking in Rezdy====##
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

                        print_r($resultArray);
                        
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

                        $attemps = 'Rezdy Booking';
                        $this->updateRezdyOrder($rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);

                        if ($resultArray['requestStatus']['success'] == true) {

                            ##Delete session data
                            $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                            $where = array(
                                'sessionID' => $_POST['rezdy_session_id'],
                            );
                            $wpdb->delete($table_add_to_cart_data, $where);



                            session_destroy();
                            wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                        } else {
                            session_destroy();
                            wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                        }
                    } else {
                        session_destroy();
                        wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => $error));
                    }
                    
                }
            }
        }

        // wp_send_json(array('itemParams' => $response));
    }

    public function paypal_request($apiUrl, $post_data, $request_type, $headers)
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
    public function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    public function get_user_ip()
    {
        // Check for shared Internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // Check for IP addresses passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if multiple IP addresses exist in the X-Forwarded-For header
            $ip_addresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($ip_addresses as $ip) {
                if ($this->validate_ip($ip)) {
                    return $ip;
                }
            }
        }

        // Check for the remote address
        if (!empty($_SERVER['REMOTE_ADDR']) && $this->validate_ip($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        // Unable to retrieve the IP address
        return 'Unknown';
    }

    public function updateOrder($success_message, $failure_message, $rezdy_order_id, $transactionID, $order_status, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
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
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Payment status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    // ========= Airwallex update order ====
    public function airwallexUpdateOrder($success_message, $failure_message, $rezdy_order_id, $transactionID, $order_status, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
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
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Payment status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }
    // =====================================

    // ===== airwallex failed ======
    public function failedAirwallex($failure_message, $order_status, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
    {
        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'failure_message' => $failure_message,
            'order_status' => $order_status,
            'response_time' => current_time('mysql')
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Reason:" . $failure_message . PHP_EOL . "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }
    // =============================

    public function updateRezdyOrder($rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
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
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Rezdy booking status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function failedStripe($failure_message, $order_status, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
    {
        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'failure_message' => $failure_message,
            'order_status' => $order_status,
            'response_time' => current_time('mysql')
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Reason:" . $failure_message . PHP_EOL . "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    // Function to validate an IP address
    public function validate_ip($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
    }

    public function succcess_render()
    {
        $this->renderTemplate('success.php', []);
    }

    public function cancel_render()
    {
        $this->renderTemplate('cancel.php', []);
    }

    public function return_render()
    {
        $this->renderTemplate('return.php', []);
    }

    function delete_db_sessions_callback()
    {

        global $wpdb;
        $_ARRAY_SESSION = array();
        $session_id = $_POST['rezdy_session_id'];
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

        // echo '<pre>';
        // print_r($_ARRAY_SESSION);
        // exit();

        $removed = false;
        $totalPrice = 0;
        if (isset($_POST['itemType']) && !empty($_POST['itemType'])) {
            $dataCode = $_POST['dataCode'];
            $code_type = $_POST['itemType'];
            if ($_POST['itemType'] == 'Voucher') {

                if (isset($_ARRAY_SESSION[0]['voucherCode']) && !empty($_ARRAY_SESSION[0]['voucherCode']['codes'])) {
                    foreach ($_ARRAY_SESSION[0]['voucherCode']['codes'] as $codeIndex => $codeRowData) :
                        if ($codeIndex == $dataCode) {
                            $amountToRemove = $codeRowData['totalPaid'];
                            unset($_ARRAY_SESSION[0]['voucherCode']['codes'][$codeIndex]);
                            $removed = true;
                        } else {
                            $totalPrice += $codeRowData['totalPaid'];
                        }

                    endforeach;
                }
                if (isset($_ARRAY_SESSION[0]['couponCode']) && !empty($_ARRAY_SESSION[0]['couponCode']['code'])) {
                    foreach ($_ARRAY_SESSION[0]['couponCode']['code'] as $codeIndex => $codeRowData) :
                        $totalPrice += $_ARRAY_SESSION[0]['couponCode']['code'][$codeIndex]['totalPaid'];
                    endforeach;
                }

                $_ARRAY_SESSION[0]['codeData']['alltotalDue'] += $amountToRemove;
                $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] = $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] - $amountToRemove;

                $totalPrice = $_ARRAY_SESSION[0]['codeData']['alltotalDue'];
            }
            if ($_POST['itemType'] == 'PromoCode') {
                if (isset($_ARRAY_SESSIONp[0]['voucherCode']) && !empty($_ARRAY_SESSION[0]['voucherCode']['codes'])) {
                    $totalPrice = 0;
                    foreach ($_ARRAY_SESSION[0]['voucherCode']['codes'] as $codeIndex => $codeRowData) :
                        $totalPrice += $codeRowData['totalPaid'];
                    endforeach;
                }
                if (isset($_ARRAY_SESSION[0]['couponCode']) && !empty($_ARRAY_SESSION[0]['couponCode']['code'])) {
                    foreach ($_ARRAY_SESSION[0]['couponCode']['code'] as $codeIndex => $codeRowData) :
                        $amountToRemove = $codeRowData['totalPaid'];
                    endforeach;
                }

                $_ARRAY_SESSION[0]['codeData']['alltotalDue'] += $amountToRemove;
                $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] = $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] - $amountToRemove;

                $totalPrice = $_ARRAY_SESSION[0]['codeData']['alltotalDue'];

                unset($_ARRAY_SESSION[0]['couponCode']);
                $removed = true;
            }

            if (empty($_ARRAY_SESSION[0]['voucherCode']['codes']) && (!isset($_ARRAY_SESSION[0]['couponCode']) || empty($_ARRAY_SESSION[0]['couponCode']['code']))) {
                unset($_ARRAY_SESSION[0]['voucherCode']);
                unset($_ARRAY_SESSION[0]['couponCode']);
                unset($_ARRAY_SESSION[0]['codeData']);
            }


            ##Update sessionData
            $session_data_to_update = array(
                'sessionData' => json_encode($_ARRAY_SESSION[0])
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);

            wp_send_json(array('codeRemoved' => $removed, 'code_type' => $code_type, 'code' => $dataCode, 'totalDuePrice' => $totalPrice));
        } else {

            if (isset($_ARRAY_SESSION[0]['codeData'])) {
                unset($_ARRAY_SESSION[0]['voucherCode']);
                unset($_ARRAY_SESSION[0]['couponCode']);
                unset($_ARRAY_SESSION[0]['codeData']);
            }


            if (isset($_POST['sessionID']) && isset($session_id)) {
                $sessionID = $_POST['sessionID'];
                foreach ($_ARRAY_SESSION[0][$session_id] as $key => $sessionData) :
                    if ($sessionData['schedule_time'] == $sessionID) {
                        unset($_ARRAY_SESSION[0][$session_id][$key]);
                        $_ARRAY_SESSION[0][$session_id] = array_values($_ARRAY_SESSION[0][$session_id]);
                        $removed = true;
                    }
                endforeach;

                if (count($_ARRAY_SESSION[0][$session_id]) > 0) {
                    foreach ($_ARRAY_SESSION[0][$session_id] as $k => $detail) :
                        $totalPrice += $detail['totalPrice'];
                    endforeach;
                } else {
                    session_destroy();
                }
            }


            ##Update sessionData
            $session_data_to_update = array(
                'sessionData' => json_encode($_ARRAY_SESSION[0])
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);


            wp_send_json(array('response' => $removed, 'totalPrice' => number_format($totalPrice, 2, '.', '')));
        }
    }

    function edit_booking_callback()
    {
        // echo '<pre>';
        // print_r($_COOKIE);
        // exit();


        global $wpdb;
        $_ARRAY_SESSION = array();
        $session_id = $_POST['rezdy_session_id'];

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


        if (isset($_ARRAY_SESSION[0]['voucherCode']) || isset($_ARRAY_SESSION[0]['couponCode']) || isset($_ARRAY_SESSION[0]['codeData'])) {
            unset($_ARRAY_SESSION[0]['voucherCode']);
            unset($_ARRAY_SESSION[0]['couponCode']);
            unset($_ARRAY_SESSION[0]['codeData']);
        }



        $guzzleClient           = new RezdyAPI(get_option('cc_rezdy_api_key'));
        $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['session_date']));
        $lastDate = date("Y-m-t", strtotime($selected_date));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));
        $availabilitySearch = new SessionSearch([
            'productCode' => $_POST['product_code'],
            'startTimeLocal' => $selected_date,
            'endTimeLocal' => $lastDateTime,
            'limit'             => 500
        ]);
        $availabilities = $guzzleClient->availability->search($availabilitySearch);
        $response = '';
        foreach ($availabilities->sessions as $key => $availability) {

            if ($availability->id == $_POST['schedule_time']) {

                if ($availability->seatsAvailable < $_POST['total_quantity']) {
                    $response = array('response' => false, 'error' => 'Not enough availability');
                } else {

                    $totalPrice = 0;
                    $quantity = 0;
                    foreach ($_POST['ItemQuantity'] as $k => $option) {
                        foreach ($_ARRAY_SESSION[0][$session_id] as $j => $sessionData) {

                            if ($_POST['schedule_time'] == $sessionData['schedule_time']) {

                                $i = 0;
                                foreach ($sessionData['priceOptions'] as $newKey => $optionNew) {

                                    if ($k == $optionNew['priceOptionID']) {

                                        if (str_contains($optionNew['label'], 'Group')) {
                                            $found = $this->getGroupValue($option[$newKey]['quantity'], $optionNew['label']);
                                            if ($found) {
                                                $_ARRAY_SESSION[0][$session_id][$j]['priceOptions'][$newKey]['quantity'] = $option[$newKey]['quantity'];
                                                $sessionTotalPrice = $optionNew['price'];
                                                $sessionTotalPrice = number_format($sessionTotalPrice, 2, '.', '');
                                                $_ARRAY_SESSION[0][$session_id][$j]['priceOptions'][$newKey]['sessionTotalPrice'] = $sessionTotalPrice;
                                                $totalPrice = $totalPrice + $sessionTotalPrice;
                                                $_ARRAY_SESSION[0][$session_id][$j]['totalPrice'] = number_format($totalPrice, 2, '.', '');
                                                $quantity = $quantity + $option[$newKey]['quantity'];
                                                $_ARRAY_SESSION[0][$session_id][$j]['totalQuantity'] = $quantity;
                                            }
                                        } else {

                                            $_ARRAY_SESSION[0][$session_id][$j]['priceOptions'][$newKey]['quantity'] = $option[$newKey]['quantity'];
                                            $sessionTotalPrice = $optionNew['price'] * $option[$newKey]['quantity'];
                                            $sessionTotalPrice = number_format($sessionTotalPrice, 2, '.', '');
                                            $_ARRAY_SESSION[0][$session_id][$j]['priceOptions'][$newKey]['sessionTotalPrice'] = $sessionTotalPrice;
                                            $totalPrice = $totalPrice + $sessionTotalPrice;
                                            $_ARRAY_SESSION[0][$session_id][$j]['totalPrice'] = number_format($totalPrice, 2, '.', '');
                                            $quantity = $quantity + $option[$newKey]['quantity'];
                                            $_ARRAY_SESSION[0][$session_id][$j]['totalQuantity'] = $quantity;
                                        }
                                    }
                                    $i++;
                                }
                            }
                        }
                    }



                    ##Update sessionData
                    $session_data_to_update = array(
                        'sessionData' => json_encode($_ARRAY_SESSION[0])
                    );
                    $where = array(
                        'sessionID' => $session_id,
                    );
                    $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);


                    $response = array('response' => true, 'success' => 'Booking successfully edited !!');
                }
                break;
            }
        }

        wp_send_json($response);
    }

    public function getGroupValue($x, $value)
    {

        preg_match_all('/\d+/', $value, $matches);
        $group = $matches[0];
        if (count($group) === 1) {
            if ($x == intval($group[0])) {
                return true;
            }
        } else if (count($group) === 2) {
            if ($x >= intval($group[0]) && $x <= intval($group[1])) {
                return true;
            }
        }
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    // ======= airwallex ===============
 
    public function airwallex_auth_token() {

        $api_key =  get_option('cc_airwallex_secret_api_key'); 
        $client_id = get_option('cc_airwallex_client_id');
        $airwallex_api_base_url = get_option('cc_airwallex_api_url');
        
        $url = $airwallex_api_base_url."authentication/login";
      
        $data = array("src" => "source", "text" => "test curl request");
    
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); 
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');  
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'x-client-id: ' . $client_id,
                    'x-api-key: ' . $api_key
                    
                  ));
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);

        $response = curl_exec($curl);

        curl_close($curl);

        if (curl_errno($curl)) {
            echo 'cURL error: ' . curl_error($curl);
            exit();
        }
         
        $responseArray = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            
            echo 'JSON error: ' . json_last_error_msg();
        } else {
            //print_r($responseArray);
            $token = $responseArray['token'];
            //echo "Token:".$token;
        }
        
        wp_send_json(array('response' => true, 'token' => $token));
        exit();
    }

    public function get_payment_intents_id() {
        $token = $_POST['token'];
        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        $request_id = $_POST['request_id'];
        $order_id = $_POST['order_id'];
        $return_url = $_POST['return_url'];
        $fName = $_POST['fName'];
        $lName = $_POST['lName'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $merchant_customer_id = $_POST['merchant_cust_id'];
        
        $airwallex_api_base_url = get_option('cc_airwallex_api_url');
        
        $url = $airwallex_api_base_url."pa/payment_intents/create";

        $customer = array(
            "email" => $email,
            "first_name" => $fName,
            "last_name" => $lName,
            "merchant_customer_id" => "merchant_$merchant_customer_id",
            "phone_number" => $phone,
           
        );

        $data = array(
            "request_id" => $request_id,
            "amount" => $amount,
            "currency" => $currency,
            "merchant_order_id" => "Merchant_Order_$order_id",
            "return_url" => $return_url,
            "customer" => $customer,
        );
        // print_r($data);
        // exit();
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); 
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');  
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                  ));
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);

        $response = curl_exec($curl);
                   
        curl_close($curl);

        if (curl_errno($curl)) {
            echo 'cURL error: ' . curl_error($curl);
            exit();
        }
        
        //echo $response;
        $responseArray = json_decode($response, true);
        //print_r($responseArray);
        //exit();
        if (json_last_error() !== JSON_ERROR_NONE) {
            
            echo 'JSON error: ' . json_last_error_msg();
        } else {
            //print_r($responseArray);
    
        }
        
        wp_send_json(array('response' => true, 'data' => $responseArray ));
    }

    
    // ======= end =========
}

<?php

namespace CC_RezdyAPI\Frontend\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Booking;
use CC_RezdyAPI\Rezdy\Requests\Customer;
use CC_RezdyAPI\Rezdy\Requests\Monday;
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

        if (isset($_GET['debug'])) {
            var_dump($_COOKIE);
            wp_die();
        }

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

        if (isset($_GET['debug'])) {
            wp_die(json_encode([
                'results' => $results,
                'session_id' => $session_id,
                'sessionData' => $_ARRAY_SESSION[0],
                'postData' => $_POST
            ]));
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
                                    if (isset($option->priceGroupType) && $option->priceGroupType != 'EACH') {
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

                                    if (isset($option->priceGroupType) && $option->priceGroupType != 'EACH') {
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
                'session_id' => $session_id,
                'klarna_enabled' => $this->airwallexKlarna->should_display_klarna_option($_ARRAY_SESSION[0][$session_id])
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
                'session_id' => $session_id,
                'klarna_enabled' => $this->airwallexKlarna->should_display_klarna_option($_ARRAY_SESSION[0][$session_id])
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
            wp_enqueue_style('cc-rezdy-api-booking-details-style', $base . "src/assets/includes/css/booking-details-style.css", array(), rand(1000, 9999), 'all');

            wp_enqueue_script('toggle-jquery', $base . 'src/assets/includes/js/jquery-2.2.4.min.js', array(), rand(1000, 9999), false);
            wp_enqueue_script('booking-details-js', $base . 'src/assets/includes/js/booking-details.js', array(), rand(1000, 9999), false);
            wp_localize_script('booking-details-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
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
            foreach ($_POST['order'] as $key => $order) {
                if ($order['product_code'] && $order['sessionDate']) {

                    $quantities = [];
                    $participants = [];

                    foreach ($_POST['priceOptions'][$key] as $option) {
                        $quantities[] = [
                            'optionLabel' => $option['optionLabel'],
                            'value' => $option['value']
                        ];
                    }

                    foreach ($_POST['participant'][$key] as $participant) {

                        $participants[] = [
                            'fields' => [
                                ['label' => 'First Name', 'value' => $participant['first_name']],
                                ['label' => 'Last Name', 'value' => $participant['last_name']]
                            ]
                        ];
                    }

                    $itemParams['items'][] = [
                        'productCode' => $order['product_code'],
                        'startTimeLocal' => date('Y-m-d H:i:s', strtotime($order['sessionDate'])),
                        'quantities' => $quantities,
                        'participants' => $participants
                    ];
                }
            }

            $itemParams['customer'] = $customerParams;

            $itemParams['vouchers'] = [];
            if (isset($_POST['applied_voucher_codes']) && !empty($_POST['applied_voucher_codes'])) {
                foreach ($_POST['applied_voucher_codes'] as $kk => $code) :
                    array_push($itemParams['vouchers'], $code['codeName']);
                endforeach;
            }
            array_push($itemParams['vouchers'], $_POST['p_v_code']);


            // wp_send_json(array('requestStatus' => true, 'testingData' => $itemParams));
            // die();

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
                            if ($couponArray['voucher']['valueType'] == 'PERCENT' || $couponArray['voucher']['valueType'] == 'PERCENT_LIMITPRODUCT' || $couponArray['voucher']['valueType'] == 'PERCENT_LIMITCATALOG') {
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

                                //$totalPaid = ($valueType == 'PERCENT' || $valueType == 'PERCENT_LIMITPRODUCT' || $valueType == 'PERCENT_LIMITCATALOG') ? ($voucher_value / 100) * $_POST['priceValue'] : $voucher_value;
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

                $alltotalDue = number_format($alltotalDue, 2, '.', '');

                $_ARRAY_SESSION[0]['codeData']['alltotalDue'] = $alltotalDue;
                $_ARRAY_SESSION[0]['codeData']['alltotalPaid'] = $alltotalPaid;

                $productCount = count($_ARRAY_SESSION[0][$session_id]);
                $_ARRAY_SESSION[0]['codeData']['productCount'] = $productCount;

                if (isset($codeType) && isset($alltotalDue) && isset($alltotalPaid) && isset($totalPaid)) {


                    $_ARRAY_SESSION[0]['finalPrice'] = $alltotalDue; //store total price
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
        check_and_start_session();

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            global $wpdb;

            //Get Success and Cancel Url
            $success_url = get_option('cc_success_url');
            $cancel_url =  get_option('cc_cancel_url');

            //Check for //store total price start
            $_CHECK_SESSION = array();
            $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
            $query = $wpdb->prepare(
                "SELECT * FROM $table_add_to_cart_data WHERE sessionID = %s",
                $_POST['rezdy_session_id']
            );
            $results = $wpdb->get_results($query);
            if ($results && count($results) === 1) {
                $row = $results[0];
                $_CHECK_SESSION[] = json_decode($row->sessionData, true);
            }
            if ($_CHECK_SESSION[0]['finalPrice'] == $_POST['priceValue']) {
                //Check for //store total price end
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
                $AirwallexItem = 0;
                $itemParams = [];
                $items_name = [];
                $itemsPayPal = [];
                $itemsAirewallex = [];
                $itemsStripe = [];
                $itemsPayPal['intent'] = 'CAPTURE';
                $out_counter = 0;
                foreach ($_POST['order'] as $key => $order) {

                    if ($order['product_code'] && $order['sessionDate']) {

                        $items_name[] = $order['product_code'];
                        $quantities = [];
                        $participants = [];

                        foreach ($_POST['priceOptions'][$key] as $option) {
                            $optionLabel = ($option['optionLabel'] == 'Quantity') ? 'Everyone' : $option['optionLabel'];

                            $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['name'] = $order['product_code'] . ' ' . $order['sessionDate'] . ' (' . $optionLabel . ') ';
                            $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['quantity'] = $option['value'];
                            $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['unit_amount']['currency_code'] = 'EUR';
                            $itemsPayPal['purchase_units'][0]['items'][$PayPalItem]['unit_amount']['value'] = $option['price'];

                            $quantities[] = [
                                'optionLabel' => $option['optionLabel'],
                                'value' => $option['value']
                            ];

                            $itemsAirewallex['order']['products'][$AirwallexItem]['code'] = $order['product_code'];
                            $itemsAirewallex['order']['products'][$AirwallexItem]['name'] = $order['product_code'] . ' ' . $order['sessionDate'] . ' (' . $optionLabel . ') ';
                            $itemsAirewallex['order']['products'][$AirwallexItem]['quantity'] = $option['value'];
                            $itemsAirewallex['order']['products'][$AirwallexItem]['type'] = $_POST['method'] == 'KLARNA' ? 'intangible_good' : 'service';
                            $itemsAirewallex['order']['products'][$AirwallexItem]['unit_price'] = $option['price'];
                            $itemsAirewallex['order']['type'] = 'tours';

                            $PayPalItem++;
                            $AirwallexItem++;
                        }

                        foreach ($_POST['participant'][$key] as $participant) {

                            $participants[] = [
                                'fields' => [
                                    ['label' => 'First Name', 'value' => $participant['first_name']],
                                    ['label' => 'Last Name', 'value' => $participant['last_name']]
                                ]
                            ];
                        }


                        $itemParams['items'][] = [
                            'productName' => $_POST['priceOptions'][$key][0]['name'] ?? 'Default Tour Name',
                            'productCode' => $order['product_code'],
                            'startTimeLocal' => date('Y-m-d H:i:s', strtotime($order['sessionDate'])),
                            'quantities' => $quantities,
                            'participants' => $participants
                        ];
                    }
                }

                $itemParams['payments'][] = [
                    'amount' => number_format($_POST["priceValue"], 2, '.', ''),
                    'type' => $_POST['method'],
                    'currency' => "EUR"
                ];

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

                $monday_item_id = [];
                /* if ($_POST['method'] !== 'GooglePay') {
                    $monday_item_id = $this->monday->setupMondayItem($itemParams);
                } */

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

                    $paymentObject = array("rezdy_order_id" => $rezdy_order_id, "transactionID" => $transactionID, "success_message" => '', "failure_message" => '', "order_status" => 1, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail, "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => number_format($_POST["priceValue"], 2, '.', ''), "payment_method" => $payment_method, "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => $rezdy_booking_status, "rezdy_total_amount" => $rezdy_total_amount, "rezdy_total_paid" => $rezdy_total_paid, "rezdy_due_amount" => $rezdy_due_amount, "rezdy_created_date" => $rezdy_created_date, "rezdy_confirmed_date" => $rezdy_confirmed_date, "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                        "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date'],
                        "monday_item_id" => $paymentObject['monday_item_id']
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

                        //$this->tapfiliate_trigger($inserted_id, $_POST['priceValue'], $currency = 'EUR', $username, $_POST["email"]); ##For Tapfiliate

                        $this->subscribe_user_to_klaviyo($_POST);
                        session_destroy();
                        wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                    } else {
                        session_destroy();
                        wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                    }
                } else {
                    /* error_log(json_encode([
                        '$_POST' => $_POST,
                        'method' => $_POST['method']
                    ]));
                    wp_die(); */
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

                        $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => 'STRIPE', "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                            "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date'],
                            "monday_item_id" => $paymentObject['monday_item_id']
                        );

                        $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                        $inserted_id = $wpdb->insert_id;

                        #Get
                        $session_id = $_POST['rezdy_session_id'];
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
                                // $this->direct_update_monday_transaction($paymentObject['monday_item_id'], 'paid');
                            }
                        } catch (\Stripe\Exception\CardException $e) {
                            // Payment failed, handle card error

                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                        } catch (\Stripe\Exception\RateLimitException $e) {
                            // Too many requests made to the API too quickly
                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                        } catch (\Stripe\Exception\InvalidRequestException $e) {
                            // Invalid parameters were supplied to Stripe's API
                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                        } catch (\Stripe\Exception\AuthenticationException $e) {
                            // Authentication with Stripe's API failed
                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                        } catch (\Stripe\Exception\ApiConnectionException $e) {
                            // Network communication with Stripe failed
                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                        } catch (\Stripe\Exception\ApiErrorException $e) {
                            // Generic error
                            $error = $e->getError()->message;
                            $attemps = 'Stripe payment failed';
                            $userName = $_POST["fname"] . " " . $_POST["lname"];
                            $this->failedStripe($error, $order_status = 2, $attemps, $plugin_dir, $inserted_id, $userName, $_POST["email"]);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
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

                                $this->tapfiliate_trigger($inserted_id, $_POST['priceValue'], $currency = 'EUR', $userName, $_POST["email"]); ##For Tapfiliate

                                $this->subscribe_user_to_klaviyo($_POST);
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

                        $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                            "rezdy_confirmed_date" => '',
                            "monday_item_id" => $paymentObject['monday_item_id']
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

                        $result = $this->booking_quote_before_payment($itemParams); //Check Booking Object before booking
                        $resultArray = json_decode($result, true); //Check Booking Object before booking
                        $updatedItemsPayPal = $this->rezdyCurrency->convert_paypal_rates($itemsPayPal);
                        error_log(json_encode([
                            'type' => 'paypal',
                            '$_POST' => $_POST,
                            '$itemsPaypal' => $itemsPayPal,
                            '$itemParams' => $itemParams,
                            '$resultArray' => $resultArray,
                            '$updatedItemsPayPal' => $updatedItemsPayPal
                        ]));
                        // wp_die();
                        if ($resultArray['requestStatus']['success'] == true && $resultArray['booking']['totalDue'] == 0) { //Check Booking Object before booking
                            //Create order v2 url
                            $apiUrl = "$baseUrl/v2/checkout/orders";
                            // $post_data = json_encode($itemsPayPal);
                            $post_data = json_encode($updatedItemsPayPal);

                            $request_type = 'POST';
                            $auth = 'Basic ' . base64_encode($paypal_client_id . ':' . $paypal_secret_api_key);
                            $headers = [];
                            $headers[] = 'Content-Type: application/json';
                            $headers[] = 'Prefer: return=representation';
                            $headers[] =  'Authorization: ' . $auth;
                            $order_result  = $this->paypal_request($apiUrl, $post_data, $request_type, $headers);
                            $order_response = json_decode($order_result, true);
                            error_log(json_encode([
                                '$order_response' => $order_response
                            ]));

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

                                $this->subscribe_user_to_klaviyo($_POST);
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
                                // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                                wp_send_json(array('error' => $order_response['error_description']));
                            }
                        } else { //Check Booking Object before booking

                            $error = 'Issue from:  Booking Rezdy Object was not right, please try again';
                            $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . 'Error : '  . $error . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                            $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
                            $log_dir = $plugin_dir . 'src/payment_logs/paypal_logs/';

                            // Create the directory if it doesn't exist
                            if (!file_exists($log_dir)) {
                                mkdir($log_dir, 0755, true); // Recursive directory creation
                            }

                            $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                            file_put_contents($fileName, $log, FILE_APPEND);
                            // $this->direct_update_monday_transaction($paymentObject['monday_item_id']);
                            wp_send_json(array('error' => $error));
                        } //Check Booking Object before booking
                    }
                    if ($_POST['method'] == 'Airwallex') {
                        //echo '<pre>';
                        //print_r($_POST);
                        //print_r($itemsAirewallex);
                        //exit();
                        $itemParams['payments'][0]['type'] = 'CREDITCARD';

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

                        $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                            "paypal_token" => $paymentObject['paypal_token'],
                            "paypal_payer_id" => $paymentObject['paypal_payer_id'],
                            "rezdy_params" => "$rezdy_params",
                            "rezdy_response_params" => '',
                            "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                            "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                            "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                            "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                            "rezdy_payment_type" => '',
                            "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                            "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date'],
                            "monday_item_id" => $paymentObject['monday_item_id']
                        );

                        $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                        $inserted_id = $wpdb->insert_id;

                        #Get
                        $session_id = $_POST['rezdy_session_id'];
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

                        $result = $this->booking_quote_before_payment($itemParams); //Check Booking Object before booking
                        $resultArray = json_decode($result, true); //Check Booking Object before booking
                        error_log(json_encode([
                            'type' => 'airwallex - card',
                            '$_POST' => $_POST,
                            '$itemParams' => $itemParams,
                            '$resultArray' => $resultArray
                        ]));
                        // wp_die();
                        if ($resultArray['requestStatus']['success'] == true && $resultArray['booking']['totalDue'] == 0) { //Check Booking Object before booking
                            $api_key = get_option('cc_airwallex_secret_api_key');
                            $client_id = get_option('cc_airwallex_client_id');
                            $airwallex_live = get_option('cc_airwallex_live');
                            $baseUrl = ($airwallex_live == 'yes') ? 'https://api.airwallex.com/api/v1/' : 'https://api-demo.airwallex.com/api/v1/';
                            $post_data = '';
                            //Get Auth Token
                            $apiUrl =  $baseUrl . 'authentication/login';
                            $request_type = 'POST';
                            $headers = [];
                            $headers[] = 'Content-Type: application/json';
                            $headers[] =  'x-api-key: ' . $api_key;
                            $headers[] =  'x-client-id: ' . $client_id;
                            $requestFor = 'login';
                            $result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                            $responseArray = json_decode($result, true);
                            error_log(json_encode([
                                '$responseArray->token' => $responseArray
                            ]));
                            if (isset($responseArray['token'])) {
                                $currencySymbolText = get_symbol_as_text();
                                $token = $responseArray['token'];

                                //Create Request ID
                                $requestId = $this->generateGUID();
                                $merchant_order_id = 'Merchant_Order_' . $this->generateGUID();
                                $merchant_customer_id = 'merchant_' . $this->generateGUID();
                                if ($requestId && $merchant_order_id && $merchant_customer_id) {

                                    $itemsAirewallex['request_id'] = $requestId;
                                    $itemsAirewallex['amount'] = number_format($_POST["priceValue"], 2, '.', '');
                                    $itemsAirewallex['currency'] = 'EUR';
                                    $itemsAirewallex['merchant_order_id'] = $merchant_order_id;
                                    $itemsAirewallex['metadata']['custom_order_details'] = 'Order from website: ' . home_url() . ' and Order table record Id is ' . $inserted_id;
                                    $itemsAirewallex['metadata']['Customer Full Name'] = $po_firstName . ' ' . $po_lastName;

                                    $itemsAirewallex['customer']['email'] = $useremail;
                                    // $itemsAirewallex['customer']['first_name'] = $po_firstName;
                                    // $itemsAirewallex['customer']['last_name'] = $po_lastName;
                                    $itemsAirewallex['customer']['merchant_customer_id'] = $merchant_customer_id;
                                    $itemsAirewallex['customer']['phone_number'] = $po_phone;

                                    $updatedItemsAirewallex = $this->rezdyCurrency->convert_airwaller_rates($itemsAirewallex);

                                    error_log(json_encode([
                                        '$itemsAirewallex' => $itemsAirewallex,
                                        '$updatedItemsAirewallex' => $updatedItemsAirewallex
                                    ]));
                                    // wp_die();

                                    //Create payment Intent ID
                                    $apiUrl = $baseUrl . 'pa/payment_intents/create';
                                    $request_type = 'POST';
                                    $headers = [];
                                    $headers[] = 'Content-Type: application/json';
                                    $headers[] =  'Authorization: Bearer ' . $token;
                                    $requestFor = 'loggedIn';
                                    // $post_data = json_encode($itemsAirewallex);
                                    $post_data = json_encode($updatedItemsAirewallex);
                                    $intent_result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                                    $intent_Array = json_decode($intent_result, true);
                                    if (isset($intent_Array['id'])) {
                                        // Success Payment
                                        $isError = false;
                                        $int_ID = $intent_Array['id'];
                                        $client_secret = $intent_Array['client_secret'];
                                        $this->subscribe_user_to_klaviyo($_POST);
                                        $this->intentLog($int_ID, $inserted_id, $username, $useremail, $custom_id);
                                    } elseif (isset($intent_Array['code'])) {
                                        // Payment Declined
                                        $isError = true;
                                        $errorCode = $intent_Array['code'];
                                        $errorMessage = $intent_Array['message'];
                                        $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                    } else {
                                        // Error in Payment
                                        $isError = true;
                                        $errorCode = 'API';
                                        $errorMessage = 'Issue from: Payment_intents API';
                                        $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                    }
                                } else {
                                    // Error is not getting GUID
                                    $isError = true;
                                    $errorCode = 'API';
                                    $errorMessage = 'Issue from:  GUID is not creating';
                                    $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                }
                            } else {

                                // Error is not getting token
                                $isError = true;
                                $errorCode = 'API';
                                $errorMessage = 'Issue from:  Token is not creating from Auth API';
                                $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                            }
                        } else { //Check Booking Object before booking
                            $isError = true;
                            $errorCode = 'API';
                            $errorMessage = 'Issue from:  Booking Rezdy Object was not right, please try again';
                            $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                        } //Check Booking Object before booking

                        $errorCode = $errorCode ?? '';
                        $errorMessage = $errorMessage ?? '';
                        wp_send_json(array('isError' => $isError, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, 'int_ID' => $int_ID, 'client_secret' => $client_secret, 'inserted_id' => $inserted_id, 'rezdy_params' => $rezdy_params, 'plugin_dir' => $plugin_dir, 'username' => $username, 'useremail' => $useremail, 'custom_id' => $custom_id));
                    }
                    if ($_POST['method'] == 'KLARNA') {
                        $itemParams['payments'][0]['type'] = 'CREDITCARD';

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
                        $klarna_uid = $this->airwallexKlarna->generateGUID();

                        $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                            "paypal_token" => $paymentObject['paypal_token'],
                            "paypal_payer_id" => $paymentObject['paypal_payer_id'],
                            "rezdy_params" => "$rezdy_params",
                            "rezdy_response_params" => '',
                            "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                            "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                            "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                            "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                            "rezdy_payment_type" => '',
                            "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                            "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date'],
                            "monday_item_id" => $paymentObject['monday_item_id'],
                            "klarna_uid" => $klarna_uid
                        );

                        $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                        $inserted_id = $wpdb->insert_id;

                        #Get
                        $session_id = $_POST['rezdy_session_id'];
                        $_SESSION['rezdy_session_id'] = $session_id;
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
                        $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';

                        $paymentstatus = 'Klarna payment started';
                        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                        $log_dir = $plugin_dir . 'src/payment_logs/klarna_logs/';

                        // Create the directory if it doesn't exist
                        if (!file_exists($log_dir)) {
                            mkdir($log_dir, 0755, true); // Recursive directory creation
                        }

                        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                        file_put_contents($fileName, $log, FILE_APPEND);

                        $result = $this->booking_quote_before_payment($itemParams);
                        $resultArray = json_decode($result, true);
                        if ($resultArray['requestStatus']['success'] == true && $resultArray['booking']['totalDue'] == 0) { //Check Booking Object before booking
                            $requestId = $this->generateGUID();
                            $merchant_order_id = 'Merchant_Order_' . $this->generateGUID();
                            $merchant_customer_id = 'merchant_' . $this->generateGUID();
                            if ($requestId && $merchant_order_id && $merchant_customer_id) {
                                $itemsAirewallex['request_id'] = $requestId;
                                $itemsAirewallex['amount'] = number_format($_POST["priceValue"], 2, '.', '');
                                $itemsAirewallex['currency'] = 'EUR';
                                $itemsAirewallex['merchant_order_id'] = $merchant_order_id;
                                $itemsAirewallex['metadata']['custom_order_details'] = 'Order from website: ' . home_url() . ' and Order table record Id is ' . $inserted_id;
                                $itemsAirewallex['metadata']['Customer Full Name'] = $po_firstName . ' ' . $po_lastName;

                                $itemsAirewallex['customer']['email'] = $useremail;
                                $itemsAirewallex['customer']['merchant_customer_id'] = $merchant_customer_id;
                                $itemsAirewallex['customer']['phone_number'] = $po_phone;
                                $itemsAirewallex['return_url'] = trailingslashit( get_site_url() ) . 'thank-you?k-uid=' . $klarna_uid;

                                $updatedItemsAirewallex = $this->rezdyCurrency->convert_airwaller_rates($itemsAirewallex);
                                $klarnaResponse = $this->airwallexKlarna->create_klarna_intent($updatedItemsAirewallex, $inserted_id, $klarna_uid);

                                wp_send_json($klarnaResponse);
                                wp_die();
                            } else {
                                // Error is not getting GUID
                                $isError = true;
                                $errorCode = 'API';
                                $errorMessage = 'Issue from:  GUID is not creating';
                                $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                            }
                        } else { //Check Booking Object before booking
                            $isError = true;
                            $errorCode = 'API';
                            $errorMessage = 'Issue from:  Booking Rezdy Object was not right, please try again';
                            $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                        } //Check Booking Object before booking

                        $errorCode = $errorCode ?? '';
                        $errorMessage = $errorMessage ?? '';
                        wp_send_json(array('isError' => $isError, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, 'int_ID' => $int_ID, 'client_secret' => $client_secret, 'inserted_id' => $inserted_id, 'rezdy_params' => $rezdy_params, 'plugin_dir' => $plugin_dir, 'username' => $username, 'useremail' => $useremail, 'custom_id' => $custom_id));
                    }
                    if ($_POST['method'] == 'GooglePay') {

                        //Stage 1
                        if ($_POST['stage'] == 'beforeIntent') {


                            // $monday_item_id = $this->monday->setupMondayItem($itemParams);
                            $itemParams['payments'][0]['type'] = 'OTHER';

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


                            ##Check if orderId already have or not
                            $haveInsteredID = false;
                            $session_id = $_POST['rezdy_session_id'];

                            $_ARRAY_SESSION = array();
                            $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                            $query = $wpdb->prepare(
                                "SELECT * FROM $table_add_to_cart_data WHERE sessionID = %s",
                                $session_id
                            );
                            $results = $wpdb->get_results($query);
                            if ($results && count($results) === 1) {
                                $add_to_cart_row = $results[0];
                                $_ARRAY_SESSION[] = json_decode($add_to_cart_row->sessionData, true);

                                if ($_ARRAY_SESSION[0]['inserted_id']) {
                                    $inserted_id = $_ARRAY_SESSION[0]['inserted_id'];
                                    $table_rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
                                    $query = $wpdb->prepare(
                                        "SELECT * FROM $table_rezdy_plugin_transactions WHERE id = %s AND rezdy_order_id = %s AND success_message = %s AND order_status = %d AND payment_method = %s",
                                        $inserted_id,
                                        '',
                                        '',
                                        0,
                                        $_POST['method']
                                    );
                                    $results = $wpdb->get_results($query);
                                    if ($results && count($results) === 1) {
                                        $haveInsteredID = true;
                                        $order_row = $results[0];
                                    }
                                }
                            }

                            if ($haveInsteredID == true) {
                                ##Update Intent

                                $inserted_id = $order_row->id;
                                $intent_ID = $order_row->transactionID;

                                $paymentObject = array("rezdy_order_id" => '', "transactionID" => $intent_ID, "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

                                $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

                                $data_transactions = array(
                                    'username' => $paymentObject['username'],
                                    'useremail' => $paymentObject['useremail'],
                                    'firstName' => $paymentObject['firstName'],
                                    'lastName' => $paymentObject['lastName'],
                                    'phone' => $paymentObject['phone'],
                                    'country' => $paymentObject['country'],
                                    'date_time' => $paymentObject['date_time'],
                                    'totalAmount' => $paymentObject['totalAmount'],
                                    "rezdy_params" => "$rezdy_params",
                                    "monday_item_id" => $paymentObject['monday_item_id']
                                );


                                $where_conditions = array(
                                    'id' => $inserted_id
                                );



                                $wpdb->update($rezdy_plugin_transactions, $data_transactions, $where_conditions);


                                #Get
                                $session_id = $_POST['rezdy_session_id'];
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


                                $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';


                                $paymentstatus = 'Google Pay element updated';
                                $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                                $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';

                                // Create the directory if it doesn't exist
                                if (!file_exists($log_dir)) {
                                    mkdir($log_dir, 0755, true); // Recursive directory creation
                                }

                                $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                                file_put_contents($fileName, $log, FILE_APPEND);

                                $quote_result = $this->booking_quote_before_payment($itemParams); //Check Booking Object before booking
                                $resultArray = json_decode($quote_result, true); //Check Booking Object before booking
                                if ($resultArray['requestStatus']['success'] == true && $resultArray['booking']['totalDue'] == 0) { //Check Booking Object before booking

                                    $api_key = get_option('cc_airwallex_secret_api_key');
                                    $client_id = get_option('cc_airwallex_client_id');
                                    $airwallex_live = get_option('cc_airwallex_live');
                                    $baseUrl = ($airwallex_live == 'yes') ? 'https://api.airwallex.com/api/v1/' : 'https://api-demo.airwallex.com/api/v1/';
                                    $post_data = '';
                                    //Get Auth Token
                                    $apiUrl =  $baseUrl . 'authentication/login';
                                    $request_type = 'POST';
                                    $headers = [];
                                    $headers[] = 'Content-Type: application/json';
                                    $headers[] =  'x-api-key: ' . $api_key;
                                    $headers[] =  'x-client-id: ' . $client_id;
                                    $headers[] =  'x-api-version: 2024-09-27';
                                    $requestFor = 'login';
                                    $result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                                    $responseArray = json_decode($result, true);
                                    if (isset($responseArray['token'])) {
                                        $token = $responseArray['token'];


                                        //Create Request ID
                                        $requestId = $this->generateGUID();
                                        if ($requestId) {

                                            $itemsAirewallex['request_id'] = $requestId;
                                            $itemsAirewallex['amount'] = number_format($_POST["priceValue"], 2, '.', '');
                                            $itemsAirewallex['metadata']['Customer Full Name'] = $po_firstName . ' ' . $po_lastName;

                                            $itemsAirewallex['customer']['email'] = $useremail;
                                            $itemsAirewallex['customer']['phone_number'] = $po_phone;




                                            //Update payment Intent
                                            $apiUrl = $baseUrl . 'pa/payment_intents/' . $intent_ID . '/update';
                                            $request_type = 'POST';
                                            $headers = [];
                                            $headers[] = 'Content-Type: application/json';
                                            $headers[] =  'Authorization: Bearer ' . $token;
                                            $headers[] =  'x-api-version: 2024-09-27';
                                            $requestFor = 'loggedIn';
                                            $post_data = json_encode($itemsAirewallex);
                                            $intent_result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                                            $intent_Array = json_decode($intent_result, true);
                                            if (isset($intent_Array['id'])) {
                                                // Success Payment
                                                $isError = false;
                                                $int_ID = $intent_Array['id'];
                                                $client_secret = $intent_Array['client_secret'];
                                                $intentAmount = $intent_Array['amount'];
                                                $intentCurrency = $intent_Array['currency'];
                                                $this->intentLogGooglePayOnUpdate($int_ID, $inserted_id, $username, $useremail, $custom_id);
                                            } elseif (isset($intent_Array['code'])) {
                                                // Payment Declined
                                                $isError = true;
                                                $errorCode = $intent_Array['code'];
                                                $errorMessage = $intent_Array['message'];
                                                // $this->indirect_update_monday_transaction($intent_ID, 'failed');
                                                $this->errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                            } else {
                                                // Error in Payment
                                                $isError = true;
                                                $errorCode = 'API';
                                                $errorMessage = 'Issue from: Payment_intents API while updating';
                                                // $this->indirect_update_monday_transaction($intent_ID, 'failed');
                                                $this->errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                            }
                                        } else {
                                            // Error is not getting GUID
                                            $isError = true;
                                            $errorCode = 'API';
                                            $errorMessage = 'Issue from:  GUID is not creating while updating';
                                            // $this->indirect_update_monday_transaction($intent_ID, 'failed');
                                            $this->errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                        }
                                    } else {

                                        // Error is not getting token
                                        $isError = true;
                                        $errorCode = 'API';
                                        $errorMessage = 'Issue from:  Token is not creating from Auth API while updating';
                                        $this->errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                    }
                                } else { //Check Booking Object before booking
                                    $isError = true;
                                    $errorCode = 'API';
                                    $errorMessage = 'Issue from:  Booking Rezdy Object was not right, please try again';
                                    $this->errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                } //Check Booking Object before booking


                                wp_send_json(array('action' => 'Payment Intent Update', 'isError' => $isError, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, 'int_ID' => $int_ID, 'intentAmount' => $intentAmount, 'intentCurrency' => $intentCurrency, 'client_secret' => $client_secret, 'inserted_id' => $inserted_id, 'rezdy_params' => $rezdy_params, 'plugin_dir' => $plugin_dir, 'username' => $username, 'useremail' => $useremail, 'custom_id' => $custom_id));
                            } else {

                                ##Create Intent

                                $paymentObject = array("rezdy_order_id" => '', "transactionID" => '', "success_message" => '', "failure_message" => '', "order_status" => 0, "IP_address" => $user_ip, "username" => $username, "useremail" => $useremail,  "firstName" => $po_firstName, "lastName" => $po_lastName, "phone" => $po_phone, "country" => $po_country, "date_time" => $current_timestamp, "response_time" => '', "totalAmount" => number_format($_POST["priceValue"], 2, '.', ''),  "totalPaid" => '', "payment_method" => $_POST['method'], "paypal_token" => '', "paypal_payer_id" => '', "rezdy_booking_status" => '', "rezdy_total_amount" => '', "rezdy_total_paid" => '', "rezdy_due_amount" => '', "rezdy_created_date" => '', "rezdy_confirmed_date" => '', "monday_item_id" => count($monday_item_id) > 0 ? $monday_item_id[0] : null);

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
                                    "paypal_token" => $paymentObject['paypal_token'],
                                    "paypal_payer_id" => $paymentObject['paypal_payer_id'],
                                    "rezdy_params" => "$rezdy_params",
                                    "rezdy_response_params" => '',
                                    "rezdy_booking_status" => $paymentObject['rezdy_booking_status'],
                                    "rezdy_total_amount" => $paymentObject['rezdy_total_amount'],
                                    "rezdy_total_paid" => $paymentObject['rezdy_total_paid'],
                                    "rezdy_due_amount" => $paymentObject['rezdy_due_amount'],
                                    "rezdy_payment_type" => '',
                                    "rezdy_created_date" => $paymentObject['rezdy_created_date'],
                                    "rezdy_confirmed_date" => $paymentObject['rezdy_confirmed_date'],
                                    "monday_item_id" => $paymentObject['monday_item_id']
                                );

                                $wpdb->insert($rezdy_plugin_transactions, $data_transactions);

                                $inserted_id = $wpdb->insert_id;

                                #Get
                                $session_id = $_POST['rezdy_session_id'];
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




                                $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';


                                $paymentstatus = 'Google Pay element created';
                                $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Attempt: " . $paymentstatus . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;

                                $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';

                                // Create the directory if it doesn't exist
                                if (!file_exists($log_dir)) {
                                    mkdir($log_dir, 0755, true); // Recursive directory creation
                                }

                                $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
                                file_put_contents($fileName, $log, FILE_APPEND);

                                $quote_result = $this->booking_quote_before_payment($itemParams); //Check Booking Object before booking
                                $resultArray = json_decode($quote_result, true); //Check Booking Object before booking
                                if ($resultArray['requestStatus']['success'] == true && $resultArray['booking']['totalDue'] == 0) { //Check Booking Object before booking

                                    $api_key = get_option('cc_airwallex_secret_api_key');
                                    $client_id = get_option('cc_airwallex_client_id');
                                    $airwallex_live = get_option('cc_airwallex_live');
                                    $baseUrl = ($airwallex_live == 'yes') ? 'https://api.airwallex.com/api/v1/' : 'https://api-demo.airwallex.com/api/v1/';
                                    $post_data = '';
                                    //Get Auth Token
                                    $apiUrl =  $baseUrl . 'authentication/login';
                                    $request_type = 'POST';
                                    $headers = [];
                                    $headers[] = 'Content-Type: application/json';
                                    $headers[] =  'x-api-key: ' . $api_key;
                                    $headers[] =  'x-client-id: ' . $client_id;
                                    $headers[] =  'x-api-version: 2024-09-27';
                                    $requestFor = 'login';
                                    $result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                                    $responseArray = json_decode($result, true);
                                    if (isset($responseArray['token'])) {
                                        $token = $responseArray['token'];
                                        //Create Request ID
                                        $requestId = $this->generateGUID();
                                        $merchant_order_id = 'Merchant_Order_' . $this->generateGUID();
                                        $merchant_customer_id = 'merchant_' . $this->generateGUID();
                                        if ($requestId && $merchant_order_id && $merchant_customer_id) {

                                            $itemsAirewallex['request_id'] = $requestId;
                                            $itemsAirewallex['amount'] = number_format($_POST["priceValue"], 2, '.', '');
                                            $itemsAirewallex['currency'] = 'EUR';
                                            $itemsAirewallex['merchant_order_id'] = $merchant_order_id;
                                            $itemsAirewallex['metadata']['custom_order_details'] = 'Order from website: ' . home_url() . ' and Order table record Id is ' . $inserted_id;
                                            $itemsAirewallex['metadata']['Customer Full Name'] = $po_firstName . ' ' . $po_lastName;

                                            $itemsAirewallex['customer']['email'] = $useremail;
                                            //$itemsAirewallex['customer']['first_name'] = $po_firstName;
                                            //$itemsAirewallex['customer']['last_name'] = $po_lastName;
                                            $itemsAirewallex['customer']['merchant_customer_id'] = $merchant_customer_id;
                                            $itemsAirewallex['customer']['phone_number'] = $po_phone;




                                            //Create payment Intent ID
                                            $apiUrl = $baseUrl . 'pa/payment_intents/create';
                                            $request_type = 'POST';
                                            $headers = [];
                                            $headers[] = 'Content-Type: application/json';
                                            $headers[] =  'Authorization: Bearer ' . $token;
                                            $headers[] =  'x-api-version: 2024-09-27';
                                            $requestFor = 'loggedIn';
                                            $post_data = json_encode($itemsAirewallex);
                                            $intent_result = $this->airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor);
                                            $intent_Array = json_decode($intent_result, true);
                                            if (isset($intent_Array['id'])) {
                                                // Success Payment
                                                $isError = false;
                                                $int_ID = $intent_Array['id'];
                                                $client_secret = $intent_Array['client_secret'];
                                                $intentAmount = $intent_Array['amount'];
                                                $intentCurrency = $intent_Array['currency'];
                                                $this->intentLogGooglePay($int_ID, $inserted_id, $username, $useremail, $custom_id);
                                            } elseif (isset($intent_Array['code'])) {
                                                // Payment Declined
                                                $isError = true;
                                                $errorCode = $intent_Array['code'];
                                                $errorMessage = $intent_Array['message'];
                                                $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                            } else {
                                                // Error in Payment
                                                $isError = true;
                                                $errorCode = 'API';
                                                $errorMessage = 'Issue from: Payment_intents API';
                                                $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                            }
                                        } else {
                                            // Error is not getting GUID
                                            $isError = true;
                                            $errorCode = 'API';
                                            $errorMessage = 'Issue from:  GUID is not creating';
                                            $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                        }
                                    } else {

                                        // Error is not getting token
                                        $isError = true;
                                        $errorCode = 'API';
                                        $errorMessage = 'Issue from:  Token is not creating from Auth API';
                                        $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                    }
                                } else { //Check Booking Object before booking
                                    $isError = true;
                                    $errorCode = 'API';
                                    $errorMessage = 'Issue from:  Booking Rezdy Object was not right, please try again';
                                    $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                                } //Check Booking Object before booking


                                wp_send_json(array('action' => 'Payment Intent Create', 'isError' => $isError, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage, 'int_ID' => $int_ID, 'intentAmount' => $intentAmount, 'intentCurrency' => $intentCurrency, 'client_secret' => $client_secret, 'inserted_id' => $inserted_id, 'rezdy_params' => $rezdy_params, 'plugin_dir' => $plugin_dir, 'username' => $username, 'useremail' => $useremail, 'custom_id' => $custom_id));
                            }
                        }
                        //Stage 2
                        elseif ($_POST['stage'] == 'afterIntentSuccess') {

                            // echo '<pre>';
                            // print_r($_POST);
                            // exit();

                            //On success

                            $rezdy_session_id = $_POST['rezdy_session_id'];
                            $inserted_id = $_POST['inserted_id'];
                            $rezdy_params = $_POST['rezdy_params'];
                            $plugin_dir = $_POST['plugin_dir'];
                            $username = $_POST['username'];
                            $useremail = $_POST['useremail'];
                            $transactionID = $_POST['transactionID'];
                            $status = $_POST['status'];
                            $totalPaid = $_POST['totalPaid'];
                            $errorCode = $_POST['errorCode'];
                            $errorMessage = $_POST['errorMessage'];
                            $custom_id = $_POST['custom_id'];

                            $decoded_data = json_decode(stripslashes($rezdy_params), true);
                            $decoded_data['payments'][0]['label'] = "GooglePay - Airwallex Payment Intent ID: " . $transactionID;
                            $output_rezdy_params = json_encode($decoded_data);


                            $items_nameString = implode(", ", $items_name);
                            $site = home_url();
                            $site_without_http = trim(str_replace(array('http://', 'https://'), '', $site), '/');
                            $site_without_domain_extension = preg_replace('/\.[^.\/]+$/i', '', $site_without_http);



                            $custom_id = $inserted_id . '|' . $items_nameString . '|website ' . '( ' . $site_without_domain_extension . ' )';

                            $attemps = 'GooglePay payment completed';
                            $this->updateGooglePayOrder($status, $failure_message = '', $rezdy_order_id = '', $transactionID, $order_status = 1, $totalPaid, $attemps, $plugin_dir, $inserted_id, $username, $useremail, $custom_id);


                            ##====Create Booking in Rezdy====##
                            $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.booking_create');
                            $rezdy_api_key = get_option('cc_rezdy_api_key');
                            $apiUrl = $baseUrl;
                            $request_type = 'POST';
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, "$apiUrl");
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);

                            curl_setopt($ch, CURLOPT_POSTFIELDS, $output_rezdy_params);

                            $headers = array();
                            $headers[] = 'Content-Type: application/json';
                            $headers[] = 'Apikey: ' . $rezdy_api_key;
                            $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
                            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                            $result = curl_exec($ch);
                            curl_close($ch);
                            $resultArray = json_decode($result, true);

                            if ($resultArray['requestStatus']['success'] == true) {

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

                                $attemps = 'Rezdy Booking Successfull';
                                $this->updateGooglePayRezdyOrder($transactionID, $rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $username, $useremail, $custom_id);


                                ##Delete session data
                                $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                                $where = array(
                                    'sessionID' => $rezdy_session_id,
                                );
                                $wpdb->delete($table_add_to_cart_data, $where);

                                $this->tapfiliate_trigger($inserted_id, $totalPaid, $currency = 'EUR', $username, $useremail); ##For Tapfiliate

                                $this->subscribe_user_to_klaviyo($_POST);
                                session_destroy();
                                wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                            } else {

                                $attemps = 'Rezdy Booking NOT DONE';
                                $this->updateGooglePayRezdyOrder($transactionID, $rezdy_order_id = '', $rezdy_response_params = '', $rezdy_booking_status = 'Failed', $rezdy_total_amount = '', $rezdy_total_paid = '', $rezdy_due_amount = '', $rezdy_payment_type = '', $rezdy_created_date = '', $rezdy_confirmed_date = '', $attemps, $plugin_dir, $inserted_id, $username, $useremail, $custom_id);




                                session_destroy();
                                wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                            }
                        }

                        //Stage 3
                        elseif ($_POST['stage'] == 'afterIntentError') {
                            //On failure

                            $rezdy_session_id = $_POST['rezdy_session_id'];
                            $inserted_id = $_POST['inserted_id'];
                            $rezdy_params = $_POST['rezdy_params'];
                            $plugin_dir = $_POST['plugin_dir'];
                            $username = $_POST['username'];
                            $useremail = $_POST['useremail'];
                            $transactionID = $_POST['transactionID'];
                            $status = $_POST['status'];
                            $totalPaid = $_POST['totalPaid'];
                            $errorCode = $_POST['errorCode'];
                            $errorMessage = $_POST['errorMessage'];
                            $custom_id = $_POST['custom_id'];


                            $this->errorLogGooglePay($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                            $transactionID = ($transactionID == '') ? 'payment_intentError' : $transactionID;
                            session_destroy();
                            // $this->indirect_update_monday_transaction($transactionID, 'failed');
                            wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Payment failed', 'transactionID' => $transactionID));
                        }
                    }
                }
            } else {
                wp_send_json(array('paymentStatus' => false, 'cancel_url' => $cancel_url));
            }
        }

        // wp_send_json(array('itemParams' => $response));
    }

    /**
     * Subscribes a profile to a number of klaviyo lists.
     *
     * @param array $data The input data containing email, first/last name, and opt-in status.
     * @return bool True on success, false on failure.
     */
    function subscribe_user_to_klaviyo($data) {
        try {
            $private_api_key = get_option('cc_klaviyo_api_key');
            $api_revision = '2024-05-15';

            if (empty($private_api_key)) {
                error_log('[Klaviyo Subscription] Error: Klaviyo Private API Key is not set.');
                return false;
            }

            $is_opt_in = !empty($data['extra__newsletter']);
            $email = !empty($data['email']) ? sanitize_email($data['email']) : '';
            $first_name = !empty($data['fname']) ? sanitize_text_field($data['fname']) : '';
            $last_name = !empty($data['lname']) ? sanitize_text_field($data['lname']) : '';
            $phone = !empty($data['phone']) ? sanitize_text_field($data['phone']) : '';

            if (!$is_opt_in) {
                return true;
            }

            if (empty($email) || !is_email($email)) {
                error_log('[Klaviyo Subscription] Error: Invalid or empty email provided.');
                return false;
            }

            $originListIds = read_local_data('klaviyo_lists.json');
            $toListIds = $this->prepare_klaviyo_data($data['order'], $originListIds);

            if (!is_array($toListIds) || empty($toListIds)) {
                error_log('[Klaviyo Subscription] Error: No valid list IDs provided for ' . $email);
                return false;
            }

            error_log('[Klaviyo Subscription] Initiating single-request subscription for: ' . $email . ' | Lists: ' . implode(', ', $toListIds));

            $lists_data = [];
            foreach ($toListIds as $list_id) {
                $lists_data[] = [
                    'type' => 'list',
                    'id'   => trim($list_id),
                ];
            }

            $profile_attributes = [
                'email' => $email,
            ];

            if (!empty($first_name)) {
                $profile_attributes['first_name'] = $first_name;
            }

            if (!empty($last_name)) {
                $profile_attributes['last_name'] = $last_name;
            }

            /* if (!empty($phone)) {
                $profile_attributes['phone_number'] = $phone;
            } */

            $endpoint = 'https://a.klaviyo.com/api/profile-bulk-import-jobs';

            $payload = [
                'data' => [
                    'type' => 'profile-bulk-import-job',
                    'attributes' => [
                        'profiles' => [
                            'data' => [
                                [
                                    'type' => 'profile',
                                    'attributes' => $profile_attributes,
                                ]
                            ]
                        ]
                    ],
                    'relationships' => [
                        'lists' => [
                            'data' => $lists_data,
                        ],
                    ],
                ],
            ];

            custom_json_log(__CLASS__ . '::' . __FUNCTION__, $payload);

            $response = wp_remote_post($endpoint, [
                'method'    => 'POST',
                'headers'   => [
                    'Authorization' => 'Klaviyo-API-Key ' . $private_api_key,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'revision' => $api_revision,
                ],
                'body' => wp_json_encode($payload),
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                error_log('[Klaviyo Subscription] WordPress HTTP Error for ' . $email . ': ' . $response->get_error_message());
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);

            if ($response_code === 202) {
                error_log('[Klaviyo Subscription] Successfully submitted subscription job for ' . $email . ' in a single request.');
                return true;
            } else {
                $response_body = wp_remote_retrieve_body($response);
                $error_message = '[Klaviyo Subscription] API Error for ' . $email . '. HTTP Code: ' . $response_code;
                $klaviyo_errors = json_decode($response_body, true);
                if (isset($klaviyo_errors['errors'])) {
                    foreach ($klaviyo_errors['errors'] as $error) {
                        $error_message .= ' | Detail: ' . ($error['detail'] ?? 'N/A');
                        $error_message .= isset($error['title']) ? ' Title: ' . $error['title'] : '';
                    }
                } else {
                    $error_message .= ' | Response Body: ' . $response_body;
                }
                error_log($error_message);
                return false;
            }
        } catch (\Throwable $th) {
            error_log('[Klaviyo Subscription] A critical error occurred: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return false;
        }
    }

    /**
     * Prepares the data for Klaviyo subscription based on the order and origin list IDs.
     *
     * @param mixed $order The order data containing product codes and session dates.
     * @param mixed $originListIds The list of origin list IDs to check against.
     * @return array An array of list IDs that match the product codes in the order.
     */
    function prepare_klaviyo_data($order, $originListIds) {
        $returnData = [];
        $origin = get_option('cc_picked_color') ?? 'cdt';

        custom_json_log( __CLASS__ . '::' . __FUNCTION__, $order, $origin);

        foreach ($order as $item) {
            $code = $item['product_code'];
            $sessionDate = $item['sessionDate'];

            if ($code && $sessionDate) {
                foreach ($originListIds as $originListId) {
                    custom_json_log( __CLASS__ . '::' . __FUNCTION__, $code, $originListId);
                    if ($originListId['origin'] == $origin) {
                        $lists = $originListId['lists'];

                        foreach ($lists as $list) {
                            $tours = $list['tours'];

                            if (in_array($code, $tours)) {
                                $returnData[] = $list['id'];
                            }
                        }
                    }
                }
            }
        }

        return $returnData;
    }

    // ======= airwallex start ===============

    function airwallex_after_confirm()
    {

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            check_and_start_session();

            global $wpdb;
            $success_url = get_option('cc_success_url');
            $cancel_url =  get_option('cc_cancel_url');

            $rezdy_session_id = $_POST['rezdy_session_id'];
            $inserted_id = $_POST['inserted_id'];
            $rezdy_params = $_POST['rezdy_params'];
            $plugin_dir = $_POST['plugin_dir'];
            $username = $_POST['username'];
            $useremail = $_POST['useremail'];
            $transactionID = $_POST['transactionID'];
            $status = $_POST['status'];
            $totalPaid = $_POST['totalPaid'];
            $errorCode = $_POST['errorCode'];
            $errorMessage = $_POST['errorMessage'];
            $custom_id = $_POST['custom_id'];

            if ($_POST['isError'] == 'false') {
                //On success

                $decoded_data = json_decode(stripslashes($rezdy_params), true);
                $decoded_data['payments'][0]['label'] = "Airwallex Payment Intent ID: " . $transactionID;
                $output_rezdy_params = json_encode($decoded_data);

                $attemps = 'Airewallex payment completed';
                $this->updateAirwallexOrder($status, $failure_message = '', $rezdy_order_id = '', $transactionID, $order_status = 1, $totalPaid, $attemps, $plugin_dir, $inserted_id, $username, $useremail);


                ##====Create Booking in Rezdy====##
                $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.booking_create');
                $rezdy_api_key = get_option('cc_rezdy_api_key');
                $apiUrl = $baseUrl;
                $request_type = 'POST';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "$apiUrl");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $output_rezdy_params);

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Apikey: ' . $rezdy_api_key;
                $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                curl_close($ch);
                $resultArray = json_decode($result, true);

                if ($resultArray['requestStatus']['success'] == true) {

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

                    $attemps = 'Rezdy Booking Successfull';
                    $this->updateAirwallexRezdyOrder($transactionID, $rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $username, $useremail);


                    ##Delete session data
                    $table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
                    $where = array(
                        'sessionID' => $rezdy_session_id,
                    );
                    $wpdb->delete($table_add_to_cart_data, $where);

                    $this->tapfiliate_trigger($inserted_id, $totalPaid, $currency = 'EUR', $username, $useremail); ##For Tapfiliate

                    session_destroy();
                    wp_send_json(array('requestStatus' => true, 'success_url' => $success_url, 'transactionID' => $transactionID));
                } else {

                    $attemps = 'Rezdy Booking NOT DONE';
                    $this->updateAirwallexRezdyOrder($transactionID, $rezdy_order_id = '', $rezdy_response_params = '', $rezdy_booking_status = 'Failed', $rezdy_total_amount = '', $rezdy_total_paid = '', $rezdy_due_amount = '', $rezdy_payment_type = '', $rezdy_created_date = '', $rezdy_confirmed_date = '', $attemps, $plugin_dir, $inserted_id, $username, $useremail);




                    session_destroy();
                    wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Booking not triggered in Rezdy', 'transactionID' => $transactionID));
                }
            } else {
                //On failure
                $this->errorLog($errorCode, $errorMessage, $order_status = 2, $inserted_id, $username, $useremail, $custom_id);
                $transactionID = ($transactionID == '') ? 'payment_intentError' : $transactionID;
                session_destroy();
                wp_send_json(array('requestStatus' => false, 'cancel_url' => $cancel_url, 'error' => 'Payment failed', 'transactionID' => $transactionID));
            }
        }
    }

    public function airwallex_request($apiUrl, $post_data, $request_type, $headers, $requestFor)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($requestFor == 'loggedIn') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
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

    public function intentLog($int_ID, $inserted_id, $username, $useremail, $custom_id)
    {
        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'transactionID' => $int_ID
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        //Intent Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Intent ID Created: " . $int_ID . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }
    public function errorLog($errorCode, $errorMessage, $order_status, $inserted_id, $username, $useremail, $custom_id)
    {

        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'failure_message' => $errorMessage,
            'order_status' => $order_status,
            'response_time' => current_time('mysql')
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);



        //Error Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Error Code: " . $errorCode . PHP_EOL . "Error: " . $errorMessage . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function updateAirwallexOrder($success_message, $failure_message, $rezdy_order_id, $transactionID, $order_status, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
    {

        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'success_message' => $success_message,
            'failure_message' => $failure_message,
            'rezdy_order_id' => $rezdy_order_id,
            'order_status' => $order_status,
            'response_time' => current_time('mysql'),
            'totalPaid' => $totalPaid,
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id,
            'transactionID' => $transactionID,
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

    public function updateAirwallexRezdyOrder($transactionID, $rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail)
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
            'id' => $inserted_id,
            'transactionID' => $transactionID,
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Rezdy booking status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/airwallex_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    // ======= airwallex end =========

    //Google Pay
    public function intentLogGooglePay($int_ID, $inserted_id, $username, $useremail, $custom_id)
    {
        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'transactionID' => $int_ID
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        //Intent Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Google Pay Intent ID Created: " . $int_ID . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function intentLogGooglePayOnUpdate($int_ID, $inserted_id, $username, $useremail, $custom_id)
    {
        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'transactionID' => $int_ID
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);

        //Intent Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Google Pay Intent ID Updated: " . $int_ID . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function errorLogGooglePay($errorCode, $errorMessage, $order_status, $inserted_id, $username, $useremail, $custom_id)
    {

        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'failure_message' => $errorMessage,
            'order_status' => $order_status,
            'response_time' => current_time('mysql')
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);



        //Error Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Error Code: " . $errorCode . PHP_EOL . "Error: " . $errorMessage . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function errorLogGooglePayOnUpdate($errorCode, $errorMessage, $order_status, $inserted_id, $username, $useremail, $custom_id)
    {

        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'failure_message' => $errorMessage,
            'order_status' => $order_status,
            'response_time' => current_time('mysql')
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id, // Assuming rezdy_order_id is the unique identifier
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);



        //Error Log
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Error Code: " . $errorCode . PHP_EOL . "Error While Updating Payment Intent: " . $errorMessage . PHP_EOL . "User name: " . $username . PHP_EOL . "User email: " . $useremail . PHP_EOL . "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;
        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        // Create the directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true); // Recursive directory creation
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }


    public function updateGooglePayRezdyOrder($transactionID, $rezdy_order_id, $rezdy_response_params, $rezdy_booking_status, $rezdy_total_amount, $rezdy_total_paid, $rezdy_due_amount, $rezdy_payment_type, $rezdy_created_date, $rezdy_confirmed_date, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail, $custom_id)
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
            'id' => $inserted_id,
            'transactionID' => $transactionID,
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Rezdy booking status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    public function updateGooglePayOrder($success_message, $failure_message, $rezdy_order_id, $transactionID, $order_status, $totalPaid, $attemps, $plugin_dir, $inserted_id, $userName, $userEmail, $custom_id)
    {

        global $wpdb;
        ##Update order
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $data_to_update = array(
            'success_message' => $success_message,
            'failure_message' => $failure_message,
            'rezdy_order_id' => $rezdy_order_id,
            'order_status' => $order_status,
            'response_time' => current_time('mysql'),
            'totalPaid' => $totalPaid,
        );

        // Define the WHERE clause to identify the row to update
        $where = array(
            'id' => $inserted_id,
            'transactionID' => $transactionID,
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Payment status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $userEmail . PHP_EOL .  "Table inserted_id: " . $custom_id . PHP_EOL . "-------------------------" . PHP_EOL;


        $log_dir = $plugin_dir . 'src/payment_logs/googlepay_logs/';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $fileName = $log_dir . 'log_' . date("j.n.Y") . '.log';
        file_put_contents($fileName, $log, FILE_APPEND);
    }

    //GooglePay

    //========= Email validation Ajax start =====
    function email_validation()
    {

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST['email'];
            $response = $this->validate_email($email);
            wp_send_json(array('response' => $response));
        }
    }
    public function validate_email($email)
    {

        $response = false;
        // Validate the email format first
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Extract the domain part
            $domain = substr(strrchr($email, "@"), 1);

            // Check if the domain has a valid DNS record
            if (checkdnsrr($domain, "MX")) {
                $response = true;
            }
        }

        return $response;
    }

    //========= Email validation Ajax and =====

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

    public function tapfiliate_trigger($inserted_id, $amount, $currency, $userName, $email) ##For Tapfiliate
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
    public function tapfiliate_CURL($apiUrl, $request_type, $post_data, $headers) ##For Tapfiliate
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
        ##Update tapfiliate fields
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';

        if (!$is_conversion) {
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


        $where = array(
            'id' => $inserted_id,
        );

        // Perform the update
        $result = $wpdb->update($rezdy_plugin_transactions, $data_to_update, $where);


        ##log file update
        $log  = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . "Tapfiliate status: " . $attemps . PHP_EOL . "User name: " . $userName . PHP_EOL . "User email: " . $email . PHP_EOL .  "Table inserted_id: " . $inserted_id . PHP_EOL . "-------------------------" . PHP_EOL;

        $plugin_dir = trailingslashit(plugin_dir_path($this->appContext->getPluginFile()));
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

    public function notify_return_render()  ##IPN_HUB
    {
        $this->renderTemplate('notify_return.php', []);
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

            if ($removed == true) {
                $_ARRAY_SESSION[0]['finalPrice'] = $totalPrice; //store total price
            }

            ##Update sessionData
            $session_data_to_update = array(
                'sessionData' => json_encode($_ARRAY_SESSION[0])
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);
            wp_send_json(array(
                'codeRemoved' => $removed,
                'code_type' => $code_type,
                'code' => $dataCode,
                'totalDuePrice' => $totalPrice,
                'klarna_enabled' => $this->airwallexKlarna->should_display_klarna_option($_ARRAY_SESSION[0][$session_id])
            ));
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

            if ($removed == true) {
                $_ARRAY_SESSION[0]['finalPrice'] = $totalPrice; //store total price
            }
            ##Update sessionData
            $session_data_to_update = array(
                'sessionData' => json_encode($_ARRAY_SESSION[0])
            );
            $where = array(
                'sessionID' => $session_id,
            );
            $wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);
            wp_send_json(array(
                'response' => $removed,
                'totalPrice' => number_format($totalPrice, 2, '.', ''),
                'klarna_enabled' => $this->airwallexKlarna->should_display_klarna_option($_ARRAY_SESSION[0][$session_id])
            ));
        }
    }

    function edit_booking_callback()
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


        if (isset($_ARRAY_SESSION[0]['voucherCode']) || isset($_ARRAY_SESSION[0]['couponCode']) || isset($_ARRAY_SESSION[0]['codeData'])) {
            unset($_ARRAY_SESSION[0]['voucherCode']);
            unset($_ARRAY_SESSION[0]['couponCode']);
            unset($_ARRAY_SESSION[0]['codeData']);
        }



        $guzzleClient           = new RezdyAPI(get_option('cc_rezdy_api_key'));
        $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['session_date']));
        $lastDate = date("Y-m-d", strtotime($selected_date));
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
                    $found_schedule_time = false;
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
                                $found_schedule_time = true;
                            }
                        }
                    }

                    if ($found_schedule_time === true) {
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

    ##====Check Booking Object before booking====##
    public function booking_quote_before_payment($itemParams)
    {
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
        return $result;
    }
    ##====Check Booking Object before booking====##

    /**
     * Update the Monday transaction to either Failed or Paid status
     */
    public function update_monday_transaction()
    {
        global $wpdb;

        try {
            $transactionID = data_get($_POST, 'transactionID');
            $type = data_get($_POST, 'type', 'failed');
            $table = $wpdb->prefix . 'rezdy_plugin_transactions';

            $query = "SELECT * FROM {$table} WHERE `transactionID` = '{$transactionID}'";
            $transaction_row = $wpdb->get_row($query);

            if ($transaction_row) {
                // Update to status on Monday
                $status = $type == 'failed' ? 2 : 1;
                $this->monday->update_item_status((string) $transaction_row->monday_item_id, $status);
            }
        } catch (\Exception $e) {
            error_log(__CLASS__ . '::' . __FUNCTION__ . ' -- ' . $e->getMessage());
        }

        wp_send_json([
            'success' => true
        ]);
        wp_die();
    }

    /**
     * Direct update the Monday transaction to either Failed or Paid status
     */
    public function direct_update_monday_transaction($monday_item_id, $type = 'failed')
    {
        // Update to status on Monday
        $status = $type == 'failed' ? 2 : 1;
        $this->monday->update_item_status((string) $monday_item_id, $status);
    }

    /**
     * Indirectly update monday transaction
     */
    public function indirect_update_monday_transaction($transactionID, $type)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rezdy_plugin_transactions';

        $query = "SELECT * FROM {$table} WHERE `transactionID` = '{$transactionID}'";
        $transaction_row = $wpdb->get_row($query);

        if ($transaction_row && $transaction_row->monday_item_id) {
            // Update to status on Monday
            $status = $type == 'failed' ? 2 : 1;
            $monday = new Monday;
            $monday->update_item_status((string) $transaction_row->monday_item_id, $status);
        }
    }

    /**
     * Form Direct add Monday Items
     */
    public function direct_add_monday_item_callback()
    {
        try {
            if ($items = data_get($_POST, 'items', [])) {
                $items = json_decode(stripslashes($items), true);
                $createdItemIds = [];

                foreach ($items as $item) {
                    $participants = data_get($item, 'participants', []);
                    unset($item['participants']);

                    $item_name = data_get($item, 'item_name', 'Name not found');
                    unset($item['item_name']);

                    $createdItemIds[] = $this->monday->direct_create_monday_item(
                        $item_name,
                        json_encode($item),
                        $participants
                    );
                }

                wp_send_json([
                    'status' => true,
                    'message' => 'Monday Items created',
                    'monday_item_ids' => $createdItemIds
                ]);
                wp_die();
            }
        } catch (\Exception $e) {
            error_log(__CLASS__ . '::' . __FUNCTION__ . ' -- ' . $e->getMessage());
            wp_send_json([
                'status' => false,
                'error' => 'Data is not valid',
                'message' => $e->getMessage()
            ]);
            wp_die();
        }
    }
}

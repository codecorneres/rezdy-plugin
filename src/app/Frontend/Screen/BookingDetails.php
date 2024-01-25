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

class BookingDetails extends Screen
{

    public function render()
    {   
        if (!session_id()) {
            session_start();
            $session_id = session_id();
        }
        

        if(!empty($_POST)){
            $_SESSION['postData'] = $_POST;
            $postData = $_SESSION['postData'];
        
            // echo '<pre>';
            // print_r($postData);
            // exit();
            $guzzleClient           = new RezdyAPI($this->appContext::API_KEY);
            $rezdy_api_product_code = $postData['OrderItem']['productCode'];
            $product                = $guzzleClient->products->get($rezdy_api_product_code);

            $select_date = date('Y-m-d', strtotime($postData['OrderItem']['preferredDate']));
            $TodayDate = date('Y-m-d', time());
            if($TodayDate == $select_date){
                $selected_date = date('Y-m-d H:m:s', strtotime($postData['OrderItem']['preferredDate'] . ' ' . date('H:i:s')));
            }else{
                $selected_date = date('Y-m-d H:m:s', strtotime($postData['OrderItem']['preferredDate']));
            }
            $lastDate = date("Y-m-t", strtotime($selected_date));
            $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));
            $availabilitySearch = new SessionSearch([
                'productCode' => $postData['OrderItem']['productCode'],
                'startTimeLocal' => $selected_date,
                'endTimeLocal' => $lastDateTime
            ]);
            $availabilities = $guzzleClient->availability->search($availabilitySearch);
            
            $response = array();

            foreach ($availabilities->sessions as $key => $availability) {


                if($availability->id == $postData['schedule_time']){

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
                            $quantity = $postData['ItemQuantity'][$postData['OrderItem']['productCode']][$key]['quantity'];
                            $priceOptionID = $postData['ItemQuantity'][$postData['OrderItem']['productCode']][$key]['priceOption']['id'];
                            $price = $option->price;
                            $label = $option->label;
                            $sessionTotalPrice = $quantity * $price;
                            $totalPrice += $sessionTotalPrice;
                            $totalQuantity += $quantity;
                            if($quantity > 0){
                                $priceOptions[] = [
                                    "label"        => $label,
                                    "quantity"     => $quantity,
                                    "price"        => number_format($price, 2, '.', ''),
                                    'sessionTotalPrice' => number_format($sessionTotalPrice, 2, '.', ''),
                                    'priceOptionID' => $priceOptionID,
                                ];
                            }
                        }
                        $dateTime = new DateTime($sessionDate);
                        $formattedDate = $dateTime->format('j M Y H:i');
                        $response[] = [
                            "name"          => $product->product->name,
                            "productCode"   => $product->product->productCode,
                            "sessionDate"   => $formattedDate,
                            "priceOptions"  => $priceOptions,
                            "totalPrice"    => number_format($totalPrice, 2, '.', ''),
                            "totalQuantity" => $totalQuantity,
                            "schedule_time" => $postData['schedule_time'],
                            "tour_url"      => $postData['tour_url']
                        ];
                    }
                }
            }

            

            if(isset($_SESSION[$session_id])){
                $found = false;
                foreach ($_SESSION[$session_id] as $key => $sessionData):
                    if($sessionData['schedule_time'] == $response[0]['schedule_time']){
                        $_SESSION[$session_id][$key] = $response[0];
                        $found = true;
                        break;
                    }
                endforeach;

                if (!$found) {
                    $_SESSION[$session_id][] = $response[0];
                }
            }else{
                $_SESSION[$session_id] = $response;
            }

            $this->renderTemplate('booking-details.php', [
                'product' => $product,
                'session' => $postData,
                'availabilities' => $availabilities->sessions,
                'response' => $_SESSION[$session_id],
                'quantity' => $quantity
            ]);
        }else{
            $this->renderTemplate('booking-details.php', [
                'response' => $_SESSION[$session_id]
            ]);
        }
        
    }

    public function scripts()
    {
        //
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_style('cc-rezdy-api', "{$base}src/assets/includes/css/booking-details-style.css", [], $this->appContext::SCRIPTS_VERSION);

        wp_enqueue_script('toggle-jquery', $base . 'src/assets/includes/js/jquery-2.2.4.min.js', [], $this->appContext::SCRIPTS_VERSION);
        wp_enqueue_script('booking-toggle-js', $base . 'src/assets/includes/js/booking-details.js', [], $this->appContext::SCRIPTS_VERSION);

        wp_localize_script('booking-toggle-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }


    function booking_checkout_callback()
    {
        echo '<pre>';
        $rezdyAPI = new RezdyAPI($this->appContext::API_KEY);

        $customerParams = [
            'firstName' => $_POST['fname'],
            'lastName' => $_POST['lname'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone']
        ];

        $itemParams = [];
        foreach ($_POST['order'] as $key => $order) {

            if ($order['product_code'] && $order['sessionDate']) {
                foreach($_POST['priceOptions'][$key] as $i => $option){

                    $itemParams['items'][$key]['productCode'] = $order['product_code'];
                    $itemParams['items'][$key]['startTimeLocal'] = date('Y-m-d H:i:s', strtotime($order['sessionDate']));
                    $itemParams['items'][$key]['quantities'][$i]['optionLabel'] = $option['optionLabel'];
                    $itemParams['items'][$key]['quantities'][$i]['value'] = $option['value'];
                }

                foreach($_POST['participant'][$key] as $p => $participant){
                    $pr = 0;
                    $itemParams['items'][$key]['participants'][$p]['fields'][$pr]['label'] = 'First Name';
                    $itemParams['items'][$key]['participants'][$p]['fields'][$pr]['value'] = $participant['first_name'];
                    $pr++;
                    $itemParams['items'][$key]['participants'][$p]['fields'][$pr]['label'] = 'Last Name';
                    $itemParams['items'][$key]['participants'][$p]['fields'][$pr]['value'] = $participant['last_name'];
                }
            }
        }
        
        $itemParams['payments'][0]['amount'] = $_POST['priceValue'];
        $itemParams['payments'][0]['type'] = 'CASH';
        $itemParams['payments'][0]['label'] = 'Payment Not paid its just for testing';
        $itemParams['customer'] = $customerParams;
        $itemParams['comments'] = $_POST['comments'];
        echo json_encode($itemParams);
        
        // wp_send_json(array('itemParams' => $response));
    }

    function delete_db_sessions_callback(){
        if (!session_id()) {
            session_start();
        }
        $removed = false;
        if(isset($_POST['sessionID']) && isset($_POST['checkout_id'])){
            $sessionID = $_POST['sessionID'];
            $checkout_id = $_POST['checkout_id'];
            foreach ($_SESSION[$checkout_id] as $key => $sessionData):
                if($sessionData['schedule_time'] == $sessionID){
                    unset($_SESSION[$checkout_id][$key]);
                    $_SESSION[$checkout_id] = array_values($_SESSION[$checkout_id]);
                    $removed = true;
                }    
            endforeach;
            
            foreach ($_SESSION[$checkout_id] as $k => $detail) :
                $totalPrice += $detail['totalPrice']; 
            endforeach;
            
        }
        wp_send_json(array('response' => $removed, 'totalPrice' => number_format($totalPrice, 2, '.', '')));
    }

    function edit_booking_callback(){

        if (!session_id()) {
            session_start();
        }

        $guzzleClient           = new RezdyAPI($this->appContext::API_KEY);
        $select_date = date('Y-m-d', strtotime($_POST['session_date']));
        $TodayDate = date('Y-m-d', time());
        if($TodayDate == $select_date){
            $selected_date = date('Y-m-d H:m:s', strtotime($_POST['session_date'] . ' ' . date('H:i:s')));
        }else{
            $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['session_date']));
        }

        $lastDate = date("Y-m-t", strtotime($selected_date));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));
        $availabilitySearch = new SessionSearch([
            'productCode' => $_POST['product_code'],
            'startTimeLocal' => $selected_date,
            'endTimeLocal' => $lastDateTime
        ]);
        $availabilities = $guzzleClient->availability->search($availabilitySearch);
        $response = '';
        foreach ($availabilities->sessions as $key => $availability) {

            if($availability->id == $_POST['schedule_time']){

                if($availability->seatsAvailable < $_POST['total_quantity']){
                    $response = array('response' => false, 'error' => 'Not enough availability');
                }else{
                    $i = 0;
                    $totalPrice = 0;
                    $quantity = 0;
                    foreach ($_POST['ItemQuantity'] as $k => $option) {
                        foreach ($_SESSION[$_POST['checkout_id']] as $j => $sessionData) {
                            if($_POST['schedule_time'] == $sessionData['schedule_time'] && $sessionData['priceOptions'][$i]['priceOptionID'] == $k && isset($sessionData['priceOptions'][$i])){

                                $_SESSION[$_POST['checkout_id']][$j]['priceOptions'][$i]['quantity'] = $option[$i]['quantity'];
                                $sessionTotalPrice = $sessionData['priceOptions'][$i]['price'] * $option[$i]['quantity'];
                                $sessionTotalPrice = number_format($sessionTotalPrice, 2, '.', '');
                                $_SESSION[$_POST['checkout_id']][$j]['priceOptions'][$i]['sessionTotalPrice'] = $sessionTotalPrice;
                                $totalPrice = $totalPrice + $sessionTotalPrice;
                                $_SESSION[$_POST['checkout_id']][$j]['totalPrice'] = number_format($totalPrice, 2, '.', '');
                                $quantity = $quantity + $option[$i]['quantity'];
                                $_SESSION[$_POST['checkout_id']][$j]['totalQuantity'] = $quantity;
                                
                            }
                        }
                        $i++;
                    }
                    $response = array('response' => true, 'success' => 'Booking successfully edited !!');
                }
            break; 
            }
        }

        wp_send_json($response);
    }
}

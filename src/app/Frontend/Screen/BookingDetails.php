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
        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
        $rezdy_api_product_code = $_POST['OrderItem']['productCode'];
        $product                = $guzzleClient->products->get($rezdy_api_product_code);

        $selected_date = date('Y-m-d H:m:s', strtotime($_POST['OrderItem']['preferredDate'] . ' ' . date('H:i:s')));
        $lastDate = date("Y-m-t", strtotime($selected_date));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));
        $availabilitySearch = new SessionSearch([
            'productCode' => $_POST['OrderItem']['productCode'],
            'startTimeLocal' => $selected_date,
            'endTimeLocal' => $lastDateTime
        ]);
        $availabilities = $guzzleClient->availability->search($availabilitySearch);

        $response = [];

        foreach ($availabilities->sessions as $key => $availability) {

            $product = $guzzleClient->products->get($availability->productCode);

            $selected_date = date('Y-m-d', strtotime($_POST['OrderItem']['preferredDate']));
            $startTimeLocal = date('Y-m-d', strtotime($availability->startTimeLocal));
            $sessionDate = date('Y-m-d H:i', strtotime($availability->startTimeLocal));
            if ($selected_date == $startTimeLocal) {
                $sessionTotalPrice = 0;
                $priceOptions = [];
                $totalPrice = 0;
                $quantity = 0;
                $totalQuantity = 0;
                foreach ($availability->priceOptions as $key => $option) {
                    $quantity = $_POST['ItemQuantity'][$_POST['OrderItem']['productCode']][$key]['quantity'];
                    $price = $option->price;
                    $label = $option->label;
                    $sessionTotalPrice = $quantity * $price;
                    $totalPrice += $sessionTotalPrice;
                    $totalQuantity += $quantity;
                    $priceOptions[] = [
                        "label"        => $label,
                        "quantity"     => $quantity,
                        "price"        => number_format($price, 2, '.', ''),
                        'sessionTotalPrice' => number_format($sessionTotalPrice, 2, '.', '')
                    ];
                }
                $dateTime = new DateTime($sessionDate);
                $formattedDate = $dateTime->format('j M Y H:i');
                $response[] = [
                    "name"          => $product->product->name,
                    "productCode"          => $product->product->productCode,
                    "sessionDate"   => $formattedDate,
                    "priceOptions"  => $priceOptions,
                    "totalPrice"    => number_format($totalPrice, 2, '.', ''),
                    "totalQuantity" => $totalQuantity,
                ];
            }
        }



        $this->renderTemplate('booking-details.php', [
            'product' => $product,
            'session' => $_POST,
            'availabilities' => $availabilities->sessions,
            'response' => $response,
            'quantity' => $quantity
        ]);
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
        $rezdyAPI = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');

        $bookingParams = [
            'comments' => $_POST['comments'],
            // 'internalNotes' => 'Created From API'
        ];
        $customerParams = [
            'firstName' => $_POST['fname'],
            'lastName' => $_POST['lname'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone']
        ];
        // $itemParams = [];
        // foreach ($_POST['order'] as $order) {
        //     if ($order['productCode'] && $order['sessionDate']) {
        //         $itemParams[] = new BookingItem([
        //             'productCode' =>  $order['productCode'],
        //             'startTimeLocal' =>  date('Y-m-d H:i:s', strtotime($order['sessionDate']))
        //         ]);
        //     }
        // }
        $itemParams = [
            'productCode' =>  $_POST['order'][0]['productCode'],
            'startTimeLocal' =>  date('Y-m-d H:i:s', strtotime($_POST['order'][0]['sessionDate']))
        ];

        $quantity = [];
        foreach ($_POST['priceOptions'][0] as $params) {
            $quantity[] = new BookingItemQuantity([
                'optionLabel' => $params['optionLabel'],
                'value' => $params['value']
            ]);
        }


        $item = new BookingItem($itemParams);


        foreach ($quantity as $priceOption) {
            $item->attach($priceOption);
        }



        $booking = new Booking($bookingParams);
        $customer = new Customer($customerParams);

        $booking->attach($item);
        $booking->attach($customer);
        $booking->set(['sendNotifications' => false]);

        $response = $rezdyAPI->bookings->create($booking);
        wp_send_json(array('itemParams' => $response));
    }
}

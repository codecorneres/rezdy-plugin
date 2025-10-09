<?php

namespace CC_RezdyAPI\Frontend\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;

class FormSettings extends Screen
{
    public function render_booking_form($atts)
    {
        ob_start();

        if (isset($_GET['debug-widget']) || get_theme_mod( 'switch_to_basic_widget' )) {
            echo $this->fallback_widget();
            $output = ob_get_clean();
            return $output;
        }

        if (get_option('cc_rezdy_api_key') && get_option('cc_rezdy_api_url')) {
            $cc_rezdy_api_key = get_option('cc_rezdy_api_key');
        } else {
            echo $this->fallback_widget();
            $output = ob_get_clean();
            return $output;
        }

        $guzzleClient = new RezdyAPI($cc_rezdy_api_key);
        $rezdy_api_product_code = $atts['productcode'];

        if (! $rezdy_api_product_code) {
            echo $this->fallback_widget();
            $output = ob_get_clean();
            return $output;
        }

        $product = $guzzleClient->products->get($rezdy_api_product_code);

        if (isset($product->hadError) && $product->hadError == true) {
            echo $this->fallback_widget();
            $output = ob_get_clean();
            return $output;
        }

        $selected_date =  date('Y-m-d H:i:s');
        $lastDate = date("Y-m-t", strtotime("$selected_date"));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));

        $availabilitySearch = new SessionSearch([
            'productCode' => $rezdy_api_product_code,
            'startTimeLocal' => $selected_date,
            'endTimeLocal' => $lastDateTime,
            'limit' => 500
        ]);

        $availability = $guzzleClient->availability->search($availabilitySearch);
        $name = $product->product->name;
        $priceOptions = $product->product->priceOptions;
        $quantityRequiredMin = $product->product->quantityRequiredMin;
        $quantityRequiredMax = $product->product->quantityRequiredMax;

        if (! empty($rezdy_api_product_code)) {
            $templateFile = isset ( $_GET['dev-mode'] ) ? 'booking-form-dev.php' : 'booking-form.php';
            FormSettings::renderTemplate($templateFile, [
                'name' => $name,
                'product' => $product,
                'priceOptions' => $priceOptions,
                'quantityRequiredMin' => $quantityRequiredMin,
                'quantityRequiredMax' => $quantityRequiredMax,
                'availabilities' => $availability->sessions,
                'action_type' => $atts['action_type'] ?? 'booking',
                'atts' => $atts
            ]);
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        } else {
            echo $this->fallback_widget();
            $output = ob_get_clean();
            return $output;
        }
    }

    public function fallback_widget()
    {
        global $post;

        $html = '';

        try {
            $rezdyTheme = get_option('cc_picked_color') ?? 'theme-cdt';
            $oldWidgetSelector = '';
            $iframeUrl = '';
            $fallbackMessageStyle = '';
            $fallbackMessageTextColor = '';

            switch ($rezdyTheme) {
                case 'theme-cdt':
                    $oldWidgetSelector = 'booking_code';
                    break;
                case 'theme-rwc':
                    $oldWidgetSelector = 'booking_widgettt';
                    break;
                case 'theme-jtr':
                    $productIds = [
                        [
                            'id' => 5138,
                            'url' => 'https://carpediemrome.rezdy.com/calendarWidget/498399'
                        ],
                        [
                            'id' => 6195,
                            'url' => 'https://carpediemrome.rezdy.com/calendarWidget/PD8PYJ'
                        ],
                        [
                            'id' => 6139,
                            'url' => 'https://carpediemrome.rezdy.com/calendarWidget/581890'
                        ]
                    ];
                    foreach ($productIds as $productId) {
                        if (data_get($productId, 'id') == $post->ID) {
                            $iframeUrl = data_get($productId, 'url');
                            break;
                        }
                    }
                    $fallbackMessageStyle = 'style="
                        background-color: #0B0303;
                        border: 2px solid #AC1818;
                        border-radius: 36px;
                    "';
                    $fallbackMessageTextColor = 'color: #fff;';
                    break;
                case 'theme-tipsy':
                    $oldWidgetSelector = 'field_620f489462da7';
                    break;
                default:
                    break;
            }

            if (empty($iframeUrl)) {
                $iframeUrl = get_field($oldWidgetSelector, $post->ID);
            }

            if ($rezdyTheme == 'theme-tipsy' && (isset($_GET['debug-widget']) && $_GET['debug-widget'] == 'invalid-widget')) {
                $iframeUrl = '';
                $fallbackMessageStyle = 'style="
                    background-color: #28131F;
                "';
            }

            $html .= '<div class="booking-sidebar-widget-box in calendar-widget '. $rezdyTheme .'" ' . $fallbackMessageStyle . '>';

            if (empty($iframeUrl) || (isset($_GET['debug-widget']) && $_GET['debug-widget'] == 'invalid-widget')) {
                $html .= '<p style="margin-bottom: 2px; '. $fallbackMessageTextColor .'">' . __('The booking widget is temporarily unavailable. Please try again later or contact our support team via live chat for assistance.', 'cc-rezdy-api') . '</p>';
            } else {
                $html .= '<iframe src="' . esc_url($iframeUrl) . '" height="600"></iframe>';
            }
        } catch (\Throwable $e) {
            $html .= '<p style="margin-bottom: 2px; '. $fallbackMessageTextColor .'">' . __('The booking widget is temporarily unavailable. Please try again later or contact our support team via live chat for assistance.', 'cc-rezdy-api') . '</p>';
        }

        if (! empty($html)) {
            $html .= '</div>';
        }

        return $html;
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_style('booking-form-styles', $base . 'src/assets/includes/css/style.css', array(), time(), 'all');
        wp_enqueue_style('rezdy-currency-conversion', $base . 'src/assets/includes/css/rezdy-currency-conversion.css', array(), time(), 'all');
        wp_enqueue_style('booking-form-jquery-ui-css', $base . 'src/assets/includes/css/jquery-ui.css', array(), time(), 'all');
        //wp_enqueue_script('booking-form-jquery', $base . 'src/assets/includes/js/jquery-2.2.4.min.js', array('jquery'), rand(1000, 1000), true);
        wp_enqueue_script('booking-form-jquery-ui', $base . 'src/assets/includes/js/jquery-ui.js', array('jquery'), time(), true);
        wp_enqueue_script('rezdy-datepicker', $base . 'src/assets/includes/js/datepicker.js', array('jquery'), time(), true);

        wp_localize_script('rezdy-datepicker', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }


    function fetching_sessions_callback()
    {

        $guzzleClient  = new RezdyAPI(get_option('cc_rezdy_api_key'));

        $select_date = date('Y-m-d', strtotime($_POST['firstDate']));
        $TodayDate = date('Y-m-d', time());
        if ($TodayDate == $select_date) {
            $selected_date =  date('Y-m-d H:i:s', strtotime($_POST['firstDate'] . ' ' . date('H:i:s')));
        } else {
            $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['firstDate']));
        }


        $lastDate = date("Y-m-t", strtotime("$selected_date"));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));

        $availabilitySearch     = new SessionSearch([
            'productCode'       => $_POST['productCode'],
            'startTimeLocal'    =>  $selected_date,
            'endTimeLocal'      =>  $lastDateTime,
            'limit'             => 500
        ]);

        $availabilities = $guzzleClient->availability->search($availabilitySearch);
        $response = [];
        foreach ($availabilities->sessions as $availability) {
            $CurrentDateTime = date('Y-m-d H:i:s', time());
            if ($availability->startTimeLocal >= $CurrentDateTime) {
                $date = date('Y-m-d', strtotime($availability->startTimeLocal));
                $response[$date] = $availability;
            }
        }
        wp_send_json(array('availability' => $response, 'CurrentDateTime' => $CurrentDateTime, 'startTimeLocal' => $selected_date, 'endTimeLocal' => $lastDateTime, 'all sessions' => $availabilities->sessions, 'postDate' => $_POST['firstDate']));
    }



    function fetching_availabilities_callback()
    {
        $guzzleClient = new RezdyAPI(get_option('cc_rezdy_api_key'));
        if (isset($_POST['nextMonthClicked']) && $_POST['nextMonthClicked'] != 'false') {
            $select_date = date('Y-m-d', strtotime($_POST['firstDate']));
            $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['firstDate']));
        } else {
            $select_date = date('Y-m-d', strtotime($_POST['OrderItem']['preferredDate']));
            $TodayDate = date('Y-m-d', time());
            if ($TodayDate == $select_date) {
                $selected_date = date('Y-m-d H:m:s', strtotime($_POST['OrderItem']['preferredDate'] . ' ' . date('H:i:s')));
            } else {
                $selected_date = date('Y-m-d 00:00:00', strtotime($_POST['OrderItem']['preferredDate']));
            }
        }

        $lastDate = date("Y-m-t", strtotime($selected_date));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));

        $availabilitySearch = new SessionSearch([
            'productCode' => $_POST['OrderItem']['productCode'],
            'startTimeLocal' => $selected_date,
            'endTimeLocal' => $lastDateTime,
            'limit'             => 500
        ]);
        $availabilities = $guzzleClient->availability->search($availabilitySearch);
        $quantity = 0;
        foreach ($_POST['ItemQuantity'][$_POST['OrderItem']['productCode']] as $value) {
            $quantity += $value['quantity'];
        }

        $sessionsId = [];
        $sessionTimeLabel = [];
        $activeSession = [];
        $totalPrice = [];
        $basePrice = [];
        $response_pre = [];

        foreach ($availabilities->sessions as $availability) {
            if (isset($_POST['nextMonthClicked']) && $_POST['nextMonthClicked'] != 'false') {
                $CurrentDateTime = date('Y-m-d H:i:s', time());
                if ($availability->startTimeLocal >= $CurrentDateTime) {
                    $date_ = date('Y-m-d', strtotime($availability->startTimeLocal));
                    $response_pre[$date_] = $availability;
                }
            }

            $sessionsId[] = $availability->id;
            $date = date('Y-m-d', strtotime($availability->startTimeLocal));
            $end_date = date('Y-m-d', strtotime($availability->endTimeLocal));

            if ($date == $select_date) {
                $seatsAvailable = $availability->seatsAvailable;
                if ($quantity <= $seatsAvailable) {
                    $availabilityStatus = "$seatsAvailable Available";
                    $isActiveSession = true;
                } elseif ($seatsAvailable <= 0) {
                    $availabilityStatus = 'Sold Out';
                    $isActiveSession = false;
                } else {
                    $availabilityStatus = 'Sold Out';
                    $isActiveSession = false;
                }

                $sessionTimeLabel[' ' . $availability->id] = date('H:i', strtotime($availability->startTimeLocal)) . ' - ' . $availabilityStatus;
                $activeSession[' ' . $availability->id] = $isActiveSession;

                $sessionTotalPrice = 0;
                foreach ($availability->priceOptions as $key => $value) {

                    foreach ($_POST['ItemQuantity'][$_POST['OrderItem']['productCode']] as $IndexNew => $priceOptionNew) {
                        if ($priceOptionNew['priceOption']['label'] == $value->label) {


                            $quantity_ = $priceOptionNew['quantity'];
                            $price = convert_currency_only($value->price);

                            if (isset($value->priceGroupType) && $value->priceGroupType != 'EACH') {
                                $found = $this->getGroupValue($quantity_, $value->label);
                                if ($found) {
                                    $sessionTotalPrice = $price;
                                    $basePrice[' ' . $availability->id][$key]['id'] = $value->id;
                                    $basePrice[' ' . $availability->id][$key]['price'] = $price;
                                    $basePrice[' ' . $availability->id][$key]['label'] = $value->label;
                                    break;
                                }
                            } else {
                                $sessionTotalPrice += $quantity_ * $price;
                                $basePrice[' ' . $availability->id][$key]['id'] = $value->id;
                                $basePrice[' ' . $availability->id][$key]['price'] = $price;
                                $basePrice[' ' . $availability->id][$key]['label'] = $value->label;
                            }
                        }
                    }
                }
                $totalPrice[' ' . $availability->id] = $sessionTotalPrice;
            }
        }

        $response = [
            'sessions' => $sessionsId,
            'sessionTimeLabel' => $sessionTimeLabel,
            'activeSession'     => $activeSession,
            'totalPrice' => $totalPrice,
            'basePrice' => $basePrice,
            'availability' => $response_pre
        ];
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
}

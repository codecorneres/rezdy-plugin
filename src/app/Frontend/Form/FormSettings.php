<?php

namespace CC_RezdyAPI\Frontend\Form;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\RezdyAPI;
use Exception;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;

class FormSettings extends Screen
{

    public function render_booking_form()
    {


        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
        $page_id                = get_the_ID();
        $rezdy_api_product_code = get_post_meta($page_id, 'rezdy_product_code', true);
        $product                = $guzzleClient->products->get($rezdy_api_product_code);


        $selected_date =  date('Y-m-d H:i:s');
        $lastDate = date("Y-m-t", strtotime("$selected_date"));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));


        $availabilitySearch     = new SessionSearch([
            'productCode'       => $rezdy_api_product_code,
            'startTimeLocal'    => $selected_date,
            'endTimeLocal'      =>  $lastDateTime
            // 'startTimeLocal'    => date('Y-m-01 H:i:s'),
            // 'endTimeLocal'      => $lastDateOfMonth
        ]);

        $availability           = $guzzleClient->availability->search($availabilitySearch);
        // print_r(json_encode($availability->sessions));
        $name                   = $product->product->name;
        $priceOptions           = $product->product->priceOptions;

        if (!empty($rezdy_api_product_code)) {

            $template = FormSettings::renderTemplate('booking-form.php', [
                'name' => $name,
                'product' => $product,
                'priceOptions' => $priceOptions,
                'availabilities' => $availability->sessions,
            ]);

            return $template;
        } else {
            return 0;
        }
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_script('booking-form-script', $base . 'src/assets/includes/js/booking-form.js', array('jquery'), '1.0', true);
        wp_enqueue_style('booking-form-styles', $base . 'src/assets/includes/css/style.css', array(), '1.0', 'all');
        wp_enqueue_style('booking-form-jquery-ui-css', $base . 'src/assets/includes/css/jquery-ui.css', array(), '1.0', 'all');
        wp_enqueue_script('booking-form-jquery', $base . 'src/assets/includes/js/jquery-2.2.4.min.js', array('jquery'), '1.0', true);
        wp_enqueue_script('booking-form-jquery-ui', $base . 'src/assets/includes/js/jquery-ui.js', array('jquery'), '1.0', true);
        wp_enqueue_script('rezdy-datepicker', $base . 'src/assets/includes/js/datepicker.js', array('jquery'), '1.0', true);

        wp_localize_script('rezdy-datepicker', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }


    function ajax_action_callback()
    {
        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');


        $selected_date =  date('Y-m-d H:i:s', strtotime($_POST['firstDate']));
        $lastDate = date("Y-m-t", strtotime("$selected_date"));
        $lastDateTime = date("Y-m-d H:i:s", strtotime("$lastDate 23:59:59"));


        $availabilitySearch     = new SessionSearch([
            'productCode'       => $_POST['productCode'],
            'startTimeLocal'    =>  $selected_date,
            'endTimeLocal'      =>  $lastDateTime
        ]);

        $availabilities           = $guzzleClient->availability->search($availabilitySearch);


        $response = [];
        foreach ($availabilities->sessions as $availability) {
            $date = date('Y-m-d', strtotime($availability->startTimeLocal));
            if (!array_key_exists($date, $response)) {
                $response[$date] = $response;
            }
            $response[$date][] = $availability;
        }
        wp_send_json(array('availability' => $response));
    }
}

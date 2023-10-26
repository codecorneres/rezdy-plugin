<?php

namespace CC_RezdyAPI\Frontend\Form;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\RezdyAPI;
use Exception;

class FormSettings extends Screen
{

    public function render_booking_form()
    {

        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
        $page_id                = get_the_ID();
        $rezdy_api_product_code = get_post_meta($page_id, 'rezdy_product_code', true);
        $rezdy_res              = $guzzleClient->products->get($rezdy_api_product_code);
        $name                   = $rezdy_res->product->name;
        $priceOptions           = $rezdy_res->product->priceOptions;
        $currentDate = date('Y-m-d'); // Current date
        $lastDateOfMonth = date('Y-m-t H:i:s', strtotime($currentDate));

        $availabilitySearch     = new SessionSearch([
            'productCode'       => $rezdy_api_product_code,
            'startTimeLocal'    => '2023-11-01 00:00:00',
            'endTimeLocal'      => '2023-11-30 00:00:00'
            // 'startTimeLocal'    => date('Y-m-01 H:i:s'),
            // 'endTimeLocal'      => $lastDateOfMonth
        ]);

        $availability           = $guzzleClient->availability->search($availabilitySearch);
        if (!empty($rezdy_api_product_code)) {

            $template = FormSettings::renderTemplate('booking-form.php', [
                'name' => $name,
                'priceOptions' => $priceOptions,
                'availability' => $availability->sessions,
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
        wp_enqueue_script('booking-form-jquery', 'https://code.jquery.com/jquery-2.2.4.min.js', array('jquery'), '1.0', true);
        wp_enqueue_script('booking-form-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js', array('jquery'), '1.0', true);
        wp_enqueue_script('rezdy-datepicker', $base . 'src/assets/includes/js/datepicker.js', array('jquery'), '1.0', true);
    }
}

<?php

namespace CC_RezdyAPI\Frontend\Form;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\RezdyAPI;
use Exception;

class FormSettings extends Screen
{ 

    public function render_booking_form(){

        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
        $page_id                = get_the_ID();
        $rezdy_api_product_code = get_post_meta($page_id, 'rezdy_product_code', true);
        $rezdy_res              = $guzzleClient->products->get($rezdy_api_product_code);
        $name                   = $rezdy_res->product->name;
        $priceOptions           = $rezdy_res->product->priceOptions;  

        if (!empty($rezdy_api_product_code)) {
             //print_r($this);
            // die;
            //return "Shortcode here";
            $template = FormSettings::renderTemplate('booking-form.php', [
                            'name' => $name,
                            'priceOptions'=> $priceOptions,
                        ]);
           
            return $template;
        }
        else{
            return 0;
        }

    }

    public function scripts(){
    
    }

}

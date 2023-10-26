<?php

namespace CC_RezdyAPI\Frontend;

use CC_RezdyAPI\App;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Frontend\Form\FormSettings;

class Booking
{
    private $bookingContext;

    public function __construct(App $bookingContext)
    {

        $this->bookingContext = $bookingContext;
        add_action('init', [$this, 'rezdy_booking_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts()
    {
        return $this->callPageScreenMethod('scripts');
        // wp_enqueue_script('booking-form-script', plugin_dir_url(__FILE__) . 'includes/js/booking-form.js', array('jquery'), '1.0', true);
        // wp_enqueue_style('booking-form-styles', plugin_dir_url(__FILE__) . 'includes/css/style.css', array(), '1.0', 'all');

        // wp_enqueue_script('rezdy-date-picker-jquery', "https://code.jquery.com/jquery-2.2.4.min.js", ['jquery'],  '1.0', true);

        // wp_enqueue_script('rezdy-date-picker', "https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js", ['jquery'],  '1.0', true);
    }

    public function rezdy_booking_shortcode()
    {

        add_shortcode('rezdy_booking_form', [$this, 'booking_form_shortcode']);
    }

    public function booking_form_shortcode()
    {

        return $this->callPageScreenMethod('render_booking_form');
    }

    private function callPageScreenMethod(string $method)
    {

        return call_user_func([$this->getFormObject(FormSettings::class), $method]);
    }

    public function getFormObject(string $class)
    {

        return $this->formContext[$class] ?? ($this->formContext[$class] = new $class($this->bookingContext));
    }
}

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

        add_action('wp_ajax_ajax_action', [$this, 'ajax_action_callback']);
        add_action('wp_ajax_nopriv_ajax_action', [$this, 'ajax_action_callback']);

        add_action('wp_ajax_ajax_action_2', [$this, 'ajax_action_2_callback']);
        add_action('wp_ajax_nopriv_ajax_action_2', [$this, 'ajax_action_2_callback']);
    }


    function ajax_action_callback()
    {
        return $this->callPageScreenMethod('ajax_action_callback');
    }
    function ajax_action_2_callback()
    {
        return $this->callPageScreenMethod('ajax_action_2_callback');
    }

    public function enqueue_scripts()
    {
        return $this->callPageScreenMethod('scripts');
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

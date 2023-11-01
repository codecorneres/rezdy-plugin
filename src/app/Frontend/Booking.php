<?php

namespace CC_RezdyAPI\Frontend;

use CC_RezdyAPI\App;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Frontend\Screen\FormSettings;
use CC_RezdyAPI\Frontend\Screen\BookingDetails;

class Booking
{
    private $bookingContext;

    public function __construct(App $bookingContext)
    {

        $this->bookingContext = $bookingContext;
        add_action('init', [$this, 'rezdy_booking_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        add_action('wp_ajax_fetching_sessions', [$this, 'fetching_sessions_callback']);
        add_action('wp_ajax_nopriv_fetching_sessions', [$this, 'fetching_sessions_callback']);

        add_action('wp_ajax_fetching_availabilities', [$this, 'fetching_availabilities_callback']);
        add_action('wp_ajax_nopriv_fetching_availabilities', [$this, 'fetching_availabilities_callback']);
    }

    function fetching_sessions_callback()
    {
        return $this->callPageScreenMethod('fetching_sessions_callback');
    }
    function fetching_availabilities_callback()
    {
        return $this->callPageScreenMethod('fetching_availabilities_callback');
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

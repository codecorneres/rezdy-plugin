<?php

namespace CC_RezdyAPI\Frontend;

use CC_RezdyAPI\App;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Frontend\Screen\BookingDetails;

class Checkout
{
    private $checkoutContext;

    public function __construct(App $checkoutContext)
    {

        $this->checkoutContext = $checkoutContext;
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_booking_checkout', [$this, 'booking_checkout_callback']);
        add_action('wp_ajax_nopriv_booking_checkout', [$this, 'booking_checkout_callback']);
    }

    function booking_checkout_callback()
    {
        return $this->callPageScreenMethod('booking_checkout_callback');
    }
    public function enqueue_scripts()
    {
        return $this->callPageScreenMethod('scripts');
    }

    public function makeBooking()
    {
        return $this->callPageScreenMethod('render');
    }

    private function callPageScreenMethod(string $method)
    {
        return call_user_func([$this->getFormObject(BookingDetails::class), $method]);
    }

    public function getFormObject(string $class)
    {

        return $this->formContext[$class] ?? ($this->formContext[$class] = new $class($this->checkoutContext));
    }
}

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

        add_action('wp_ajax_quote_booking_checkout', [$this, 'quote_booking_checkout_callback']);
        add_action('wp_ajax_nopriv_quote_booking_checkout', [$this, 'quote_booking_checkout_callback']);

        add_action('wp_ajax_booking_checkout', [$this, 'booking_checkout_callback']);
        add_action('wp_ajax_nopriv_booking_checkout', [$this, 'booking_checkout_callback']);

        add_action('wp_ajax_direct_add_monday_item', [$this, 'direct_add_monday_item_callback']);
        add_action('wp_ajax_nopriv_direct_add_monday_item', [$this, 'direct_add_monday_item_callback']);

        add_action('wp_ajax_delete_db_sessions', [$this, 'delete_db_sessions_callback']);
        add_action('wp_ajax_nopriv_delete_db_sessions', [$this, 'delete_db_sessions_callback']);

        add_action('wp_ajax_edit_booking', [$this, 'edit_booking_callback']);
        add_action('wp_ajax_nopriv_edit_booking', [$this, 'edit_booking_callback']);

        //========= airwallex =====
        add_action('wp_ajax_airwallex_after_confirm', [$this, 'airwallex_after_confirm']);
        add_action('wp_ajax_nopriv_airwallex_after_confirm', [$this, 'airwallex_after_confirm']);
        // =================

        //========= Start - Airwallex Klarna =====
        add_action('wp_ajax_airwallex_klarna_payment', [$this, 'airwallex_klarna_payment']);
        add_action('wp_ajax_nopriv_airwallex_klarna_payment', [$this, 'airwallex_klarna_payment']);
        //========= End - Airwallex Klarna =====

        //========= Email validation Ajax =====
        add_action('wp_ajax_email_validation', [$this, 'email_validation']);
        add_action('wp_ajax_nopriv_email_validation', [$this, 'email_validation']);
        // =================

        //===== Update Monday Transaction =====//
        add_action('wp_ajax_update_monday_transaction', [$this, 'update_monday_transaction']);
        add_action('wp_ajax_nopriv_update_monday_transaction', [$this, 'update_monday_transaction']);
        //===== Update Monday Transaction =====//
    }

    function quote_booking_checkout_callback()
    {
        return $this->callPageScreenMethod('quote_booking_checkout_callback');
    }

    function booking_checkout_callback()
    {
        return $this->callPageScreenMethod('booking_checkout_callback');
    }

    function direct_add_monday_item_callback()
    {
        return $this->callPageScreenMethod('direct_add_monday_item_callback');
    }
    //========= airwallex =====
    function airwallex_after_confirm()
    {
        return $this->callPageScreenMethod('airwallex_after_confirm');
    }
    //========= start - airwallex klarna =====
    function airwallex_klarna_payment()
    {
        return $this->callPageScreenMethod('airwallex_klarna_payment');
    }
    // =================
    // =================

    //========= Email validation Ajax =====
    function email_validation()
    {
        return $this->callPageScreenMethod('email_validation');
    }
    // =================
    function delete_db_sessions_callback()
    {
        return $this->callPageScreenMethod('delete_db_sessions_callback');
    }
    function edit_booking_callback()
    {
        return $this->callPageScreenMethod('edit_booking_callback');
    }
    public function enqueue_scripts()
    {
        return $this->callPageScreenMethod('scripts');
    }

    public function makeBooking()
    {
        return $this->callPageScreenMethod('render');
    }

    public function successRedirect()
    {
        return $this->callPageScreenMethod('succcess_render');
    }

    public function cancelRedirect()
    {
        return $this->callPageScreenMethod('cancel_render');
    }

    public function returnRedirect()
    {
        return $this->callPageScreenMethod('return_render');
    }

    public function notify_returnRedirect() ##IPN_HUB
    {
        return $this->callPageScreenMethod('notify_return_render');
    }

    private function callPageScreenMethod(string $method)
    {
        return call_user_func([$this->getFormObject(BookingDetails::class), $method]);
    }

    public function getFormObject(string $class)
    {
        return $this->formContext[$class] ?? ($this->formContext[$class] = new $class($this->checkoutContext));
    }

    public function update_monday_transaction()
    {
        return $this->callPageScreenMethod('update_monday_transaction');
    }
}

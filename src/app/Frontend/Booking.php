<?php

namespace CC_RezdyAPI\Frontend;

use CC_RezdyAPI\App;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Frontend\Form\FormSettings;

class Booking
{   
    private $bookingContext;

    public function __construct(App $bookingContext){

        $this->bookingContext = $bookingContext;
        add_action( 'init', array($this, 'wpdocs_add_custom_shortcode') );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        
        wp_enqueue_script('booking-form-script', plugin_dir_url(__FILE__) . 'includes/js/booking-form.js', array('jquery'), '1.0', true);
        wp_enqueue_style('booking-form-styles', plugin_dir_url(__FILE__) . 'includes/css/style.css', array(), '1.0', 'all');
    }

    public function wpdocs_add_custom_shortcode() {
        
        add_shortcode('booking_form', array($this,'booking_form_shortcode'));
    }

    public function booking_form_shortcode(){ 

        $html = $this->callPageScreenMethod('render_booking_form');
        return $html;
    }

    private function callPageScreenMethod(string $method){

        return call_user_func([$this->getFormObject(FormSettings::class), $method]);  
    }

    public function getFormObject( string $class ){

        return $this->formContext[$class] ?? ( $this->formContext[$class] = new $class( $this->bookingContext ) );
    }
}

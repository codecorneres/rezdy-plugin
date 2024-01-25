<?php
/*
Plugin Name: CC Rezdy API
Plugin URI: https://codecorners.com
Description: Rezdy Integration With WordPress
Author: Code Corners
Version: 1.0
Author URI: https://codecorners.com
Text Domain: cc-rezdy-api
*/

if ( ! defined ( 'WPINC' ) ) {
    exit; // direct access
}

require_once __DIR__ . '/src/vendor/autoload.php';

(new \CC_RezdyAPI\App(__FILE__))->setup();
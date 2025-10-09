<?php
/*
Plugin Name: CC Rezdy API
Plugin URI: https://codecorners.com
Description: Rezdy Integration With WordPress
Author: Code Corners
Version: 1.7.1
Author URI: https://codecorners.com
Text Domain: cc-rezdy-api
*/

if ( ! defined ( 'WPINC' ) ) {
    exit; // direct access
}

define( 'CURRENCY_API_URL', 'https://api.exchangeratesapi.io/v1/latest' );
// define( 'CURRENCY_API_APP_ID', '18a97000cd799e274db1515f9738313c' );
define( 'CURRENCY_API_APP_ID', 'cc53d55e0587a2a77fdfe61d78c13a36' );
define( 'CURRENCY_OPTION_KEY', 'rezdy_currency_rates' );

if ( ! defined( 'PLUGIN_DIR_PATH' ) ) {
    define( 'PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) . 'src/' );
}

require_once __DIR__ . '/src/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

(new \CC_RezdyAPI\App(__FILE__))->setup();
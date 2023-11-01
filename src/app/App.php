<?php

namespace CC_RezdyAPI;

use CC_RezdyAPI\Admin\Admin;
use CC_RezdyAPI\Page\Page;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\Frontend\Checkout;

class App
{
    private $plugin_file;
    private $adminContext;
    private $pageContext;
    private $bookingContext;
    private $checkoutContext;

    const SCRIPTS_VERSION = 1659280235;
    const DB_VERSION = 1.8;
    const DB_VERSION_OPTION = 'cc:db_version';
    const SETTINGS_TABLE = 'cc_settings';
    const ALLOWED_POST_TYPE = ['rome', 'florence', 'barcelona'];

    public function __construct(string $plugin_file)
    {
        $this->plugin_file = $plugin_file;
        $this->adminContext = new Admin($this);
        $this->pageContext = new Page($this);
        $this->bookingContext = new Booking($this);
        $this->checkoutContext = new checkout($this);
    }

    public function getPluginFile(): string
    {
        return $this->plugin_file;
    }

    public function setup()
    {
        add_action('plugins_loaded', [$this, 'loaded']);

        // activation
        register_activation_hook($this->getPluginFile(), [$this, 'activation']);

        // deactivation
        register_deactivation_hook($this->getPluginFile(), [$this, 'deactivation']);
    }

    public function activation()
    {
        $db_version = (float) get_site_option(self::DB_VERSION_OPTION);

        \CC_RezdyAPI\Settings::setupDb($db_version);

        // update database version
        update_site_option(self::DB_VERSION_OPTION, self::DB_VERSION);

        flush_rewrite_rules();
    }

    public function deactivation()
    {
        flush_rewrite_rules();
    }

    public function loaded()
    {
        // REST endpoints
        add_action('rest_api_init', [$this, 'setupRestApiEndpoints']);
        add_action('init', [$this, 'custom_rewrite_rule']);
    }


    public function custom_rewrite_rule()
    {
        add_rewrite_rule('^checkout/([$\-A-Za-z0-9]*)?', 'index.php?checkout_id=$matches[1]', 'top');
        add_filter('query_vars', [$this, 'custom_query_vars'], 1, 1);
        add_action('template_redirect', [$this, 'custom_template_redirect']);
        flush_rewrite_rules();
    }

    public function custom_template_redirect()
    {
        global $wp_query;
        if (isset($wp_query->query_vars['checkout_id'])) {
            $this->checkoutContext->makeBooking('render');
        }
    }

    public function custom_query_vars($query_vars)
    {
        $query_vars[] = 'checkout_id';
        return $query_vars;
    }


    public function setupRestApiEndpoints()
    {
        register_rest_route('cc-rezdy-api/v1', '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => '',
            'permission_callback' => '__return_true',
        ]);
    }


    public static function sendMail($content)
    {
        $to = 'ashish@codecorners.com';
        // $to = 'deepak@codecorners.com';
        $subject = 'Test Mail';
        $body = $content;
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $body, $headers);
    }
}

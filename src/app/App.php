<?php

namespace CC_RezdyAPI;

use CC_RezdyAPI\Admin\Admin;
use CC_RezdyAPI\Page\Page;
use CC_RezdyAPI\Frontend\Booking;
use CC_RezdyAPI\Frontend\Checkout;
use CC_RezdyAPI\Rezdy\Util\Config;

class App
{
    private $plugin_file;
    private $adminContext;
    private $pageContext;
    private $bookingContext;
    private $checkoutContext;
    private $apiKey;

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
        $this->configContext = new Config($this);
        $BaseURl = get_option('cc_rezdy_api_url');
        if ($BaseURl) {
            $this->configContext->setBaseUrl($BaseURl);
            $this->configContext->get('endpoints.base_url');
        }
    }

    public function getPluginFile(): string
    {
        return $this->plugin_file;
    }

    public function setup()
    {
        add_action('plugins_loaded', [$this, 'loaded']);
        add_filter('acf/settings/load_json', [$this, 'my_acf_json_load_point']);
        add_filter('acf/settings/save_json', [$this, 'my_acf_json_save_point']);
        // activation
        register_activation_hook($this->getPluginFile(), [$this, 'activation']);

        // deactivation
        register_deactivation_hook($this->getPluginFile(), [$this, 'deactivation']);
    }

    function my_acf_json_save_point($path)
    {

        return plugin_dir_path(__DIR__) . '/custom_acf_json';
    }

    function my_acf_json_load_point($paths)
    {

        // Remove the original path (optional).
        unset($paths[0]);

        // Append the new path and return it.
        $paths[] = plugin_dir_path(__DIR__) . '/custom_acf_json';

        return $paths;
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
        \CC_RezdyAPI\Settings::delete_Tables_Options();
        flush_rewrite_rules();
    }

    public function loaded()
    {
        // REST endpoints
        add_action('rest_api_init', [$this, 'setupRestApiEndpoints']);
        add_action('init', [$this, 'custom_rewrite_rule']);
        add_filter('body_class', [$this, 'custom_body_class']);
    }

    public function custom_body_class($classes)
    {
        global $wp_query;

        if (isset($wp_query->query_vars['checkout_id'])) {
            $classes[] = 'custom-checkout-page';
        }

        return $classes;
    }

    public function custom_rewrite_rule()
    {
        //self::createToursPostTypes();
        add_rewrite_rule('^checkout/([$\-A-Za-z0-9]*)', 'index.php?checkout_id=$matches[1]&pagenamecustom=checkout', 'top');
        add_rewrite_rule('^success/?', 'index.php?transactionID=$matches[1]&pagenamecustom=success', 'top');
        add_rewrite_rule('^cancel/([^/]+)', 'index.php?cancel=$matches[1]', 'top');
        add_rewrite_rule('^cancel/?', 'index.php?cancel=1', 'top'); // Updated rule for the cancel page
        add_rewrite_rule('^return?([^/]+)', 'index.php?token=$matches[1]&PayerID=$matches[2]', 'top');
        add_filter('query_vars', [$this, 'custom_query_vars'], 1, 1);
        add_action('template_redirect', [$this, 'custom_template_redirect']);
        flush_rewrite_rules();
    }

    public function custom_template_redirect()
    {
        global $wp_query, $wpdb;

        if (isset($wp_query->query_vars['checkout_id'])) {
            $this->checkoutContext->makeBooking('render');
        }

        if (isset($wp_query->query_vars['transactionID']) && $wp_query->query_vars['pagenamecustom'] == 'success') {
            $this->checkoutContext->successRedirect('succcess_render');
        }

        if (isset($wp_query->query_vars['cancel'])) {
            $this->checkoutContext->cancelRedirect('cancel_render');
        }

        if (isset($wp_query->query_vars['token']) && isset($wp_query->query_vars['PayerID'])) {
            $this->checkoutContext->returnRedirect('return_render');
        }

        $successSlug = $this->getSuccessOptionUrl();
        if (isset($wp_query->query_vars['pagename']) && $wp_query->query_vars['pagename'] == $successSlug) {
            $current_url = esc_url_raw(add_query_arg(NULL, NULL));
            $urlComponents = parse_url($current_url);
            $queryString = $urlComponents['query'];
            parse_str($queryString, $parameters);
            $transactionID = $parameters['transactionID'];

            $table_name = $wpdb->prefix . 'rezdy_plugin_transactions';

            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE transactionID = %s",
                $transactionID
            );
            $results = $wpdb->get_results($query);
            foreach ($results as $result) :
                $status = $result->rezdy_booking_status;
                if ($status == 'CONFIRMED') {
                    $itemsdata = $result->rezdy_response_params;
                    $totalPaid = $result->totalPaid;
                    $firstName = $result->firstName;
                    $lastName = $result->lastName;
                    $phone = $result->phone;
                    $useremail = $result->useremail;
                    $country = $result->country;
                }
            endforeach;

            wp_enqueue_script('custom-google-tags', plugin_dir_url(__FILE__) . 'includes/js/custom-google-tags.js', array(), null, true);
            wp_localize_script('custom-google-tags', 'googleTagsData', array(
                'transactionId' => $transactionID,
                'itemsdata' => $itemsdata,
                'totalPaid' => $totalPaid,
                'fname' => $firstName,
                'lname' => $lastName,
                'phone' => $phone,
                'email' => $useremail,
                'country' => $country,
            ));
        }
    }

    public function custom_query_vars($query_vars)
    {
        $query_vars[] = 'checkout_id';
        $query_vars[] = 'transactionID';
        $query_vars[] = 'cancel';
        $query_vars[] = 'token';
        $query_vars[] = 'PayerID';
        $query_vars[] = 'pagename';
        $query_vars[] = 'pagenamecustom';
        return $query_vars;
    }

    public function getSuccessOptionUrl()
    {

        $success_url = get_option('cc_success_url');
        $urlComponents = parse_url($success_url);
        $path = $urlComponents['path'];
        $slugs = explode('/', trim($path, '/'));
        return $slugs[0];
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
        $to = 'rahul@codecorners.com';
        $subject = 'Test Mail';
        $body = $content;
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $body, $headers);
    }

    public static function custom_logs($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $logMessage = "\n" . date('Y-m-d h:i:s') . " :: " . $message;

        // Use error handling
        try {
            file_put_contents("../custom_logs.log", $logMessage, FILE_APPEND);
            // echo 'Log entry added successfully.';
        } catch (Exception $e) {
            //echo 'Error writing to log: ' . $e->getMessage();
        }
    }

    public static function createToursPostTypes()
    {
        $labels = [
            "name" => esc_html__("Tours", "custom-post-type-ui"),
            "singular_name" => esc_html__("Tour", "custom-post-type-ui"),
        ];

        $args = [
            "label" => esc_html__("Tours", "custom-post-type-ui"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => true,
            "rewrite" => ["slug" => "tours", "with_front" => false],
            "query_var" => true,
            "supports" => ["title", "editor", "thumbnail", "excerpt"],
            "show_in_graphql" => false,
        ];

        register_post_type("tours", $args);

        /**
         * Post Type: Rome Tours.
         */

        $labels = [
            "name" => esc_html__("Rome Tours", "custom-post-type-ui"),
            "singular_name" => esc_html__("Rome", "custom-post-type-ui"),
        ];

        $args = [
            "label" => esc_html__("Rome Tours", "custom-post-type-ui"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "rome", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor", "thumbnail", "excerpt", "custom-fields"],
            "taxonomies" => ["loactions", "locations_type", "offer"],
            "show_in_graphql" => false,
        ];

        register_post_type("rome", $args);

        /**
         * Post Type: Florence Tours.
         */

        $labels = [
            "name" => esc_html__("Florence Tours", "custom-post-type-ui"),
            "singular_name" => esc_html__("Florence", "custom-post-type-ui"),
        ];

        $args = [
            "label" => esc_html__("Florence Tours", "custom-post-type-ui"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => ["slug" => "florence", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor", "thumbnail", "excerpt", "custom-fields"],
            "taxonomies" => ["loactions", "offer"],
            "show_in_graphql" => false,
        ];

        register_post_type("florence", $args);

        /**
         * Post Type: Barcelona Tours.
         */

        $labels = [
            "name" => esc_html__("Barcelona Tours", "custom-post-type-ui"),
            "singular_name" => esc_html__("Barcelona", "custom-post-type-ui"),
        ];

        $args = [
            "label" => esc_html__("Barcelona Tours", "custom-post-type-ui"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => true,
            "can_export" => false,
            "rewrite" => ["slug" => "barcelona", "with_front" => true],
            "query_var" => true,
            "supports" => ["title", "editor", "thumbnail", "page-attributes"],
            "taxonomies" => ["loactions", "offer"],
            "show_in_graphql" => false,
        ];

        register_post_type("barcelona", $args);
    }
}

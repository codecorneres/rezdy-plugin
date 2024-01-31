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
    private $apiKey;

    const SCRIPTS_VERSION = 1659280235;
    const DB_VERSION = 1.8;
    const DB_VERSION_OPTION = 'cc:db_version';
    const SETTINGS_TABLE = 'cc_settings';
    const ALLOWED_POST_TYPE = ['rome', 'florence', 'barcelona'];
    const API_KEY = 'bbd855b6152a4bcdb9f4ab1eff1c3b94';

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
        add_filter( 'acf/settings/load_json', [$this, 'my_acf_json_load_point'] );
        add_filter( 'acf/settings/save_json', [$this, 'my_acf_json_save_point'] );
        // activation
        register_activation_hook($this->getPluginFile(), [$this, 'activation']);

        // deactivation
        register_deactivation_hook($this->getPluginFile(), [$this, 'deactivation']);
    }

    function my_acf_json_save_point( $path ) {

        return plugin_dir_path( __DIR__ ) . '/custom_acf_json';
    }   

    function my_acf_json_load_point( $paths ) {

        // Remove the original path (optional).
        unset($paths[0]);
    
        // Append the new path and return it.
        $paths[] = plugin_dir_path( __DIR__ ) . '/custom_acf_json';

        return $paths;    
    }

    public function activation()
    {
        $db_version = (float) get_site_option(self::DB_VERSION_OPTION);

        \CC_RezdyAPI\Settings::setupDb($db_version);

        // update database version
        update_site_option(self::DB_VERSION_OPTION, self::DB_VERSION);

        //ACF fields scan from custom acf json file
        //$field_groups = $this->scan_for_field_groups();
        //$this->acf_fields($field_groups);

        flush_rewrite_rules();
    }

    public function deactivation()
    {
        flush_rewrite_rules();
    }



    public function scan_for_field_groups() {
        $field_groups = array();
    
        $json_directory = plugin_dir_path( __DIR__ ) . '/custom_acf_json';
    
        // Check if the directory exists
        if (is_dir($json_directory)) {
            // Scan the directory for JSON files
            $json_files = scandir($json_directory);
    
            foreach ($json_files as $file) {
                // Skip . and .. directories
                if ($file == '.' || $file == '..') {
                    continue;
                }
    
                // Get the full path to the JSON file
                $file_path = $json_directory . '/' . $file;
    
                // Read the contents of the JSON file
                $json_data = file_get_contents($file_path);
    
                // Decode the JSON data into a PHP array
                $field_group_data = json_decode($json_data, true);
    
                // Add the field group data to the array
                $field_groups[] = $field_group_data;
            }
        }

        return $field_groups;
    }
    public function acf_fields($field_groups) {

        foreach ($field_groups as $field_group) {
            $existing_group = acf_get_field_group($field_group['key']);
            
            if ($existing_group) {
                // Update existing field group
                acf_update_field_group($field_group);
            } else {
                // Insert new field group
                acf_add_local_field_group($field_group);
            }
        }
    }
    public function loaded()
    {
        // REST endpoints
        add_action('rest_api_init', [$this, 'setupRestApiEndpoints']);
        add_action('init', [$this, 'custom_rewrite_rule']);

    }

    public function custom_rewrite_rule()
    {   
        self::createToursPostTypes();
        add_rewrite_rule('^checkout/([$\-A-Za-z0-9]*)', 'index.php?checkout_id=$matches[1]', 'top');
        add_rewrite_rule('^success/([^/]+)', 'index.php?transactionID=$matches[1]', 'top');
        add_rewrite_rule('^cancel/([^/]+)', 'index.php?cancel=$matches[1]', 'top');
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

        if (isset($wp_query->query_vars['transactionID'])) {
            $this->checkoutContext->successRedirect('succcess_render');
        }

        if (isset($wp_query->query_vars['cancel'])) {
            $this->checkoutContext->cancelRedirect('cancel_render');
        }
    }

    public function custom_query_vars($query_vars)
    {
        $query_vars[] = 'checkout_id';
        $query_vars[] = 'transactionID';
        $query_vars[] = 'cancel';
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
        $to = 'rahul@codecorners.com';
        $subject = 'Test Mail';
        $body = $content;
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $body, $headers);
    }

    public static function custom_logs($message) { 
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

    public static function createToursPostTypes(){
        $labels = [
            "name" => esc_html__( "Tours", "custom-post-type-ui" ),
            "singular_name" => esc_html__( "Tour", "custom-post-type-ui" ),
        ];
    
        $args = [
            "label" => esc_html__( "Tours", "custom-post-type-ui" ),
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
            "rewrite" => [ "slug" => "tours", "with_front" => false ],
            "query_var" => true,
            "supports" => [ "title", "editor", "thumbnail", "excerpt" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "tours", $args );
    
        /**
         * Post Type: Rome Tours.
         */
    
        $labels = [
            "name" => esc_html__( "Rome Tours", "custom-post-type-ui" ),
            "singular_name" => esc_html__( "Rome", "custom-post-type-ui" ),
        ];
    
        $args = [
            "label" => esc_html__( "Rome Tours", "custom-post-type-ui" ),
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
            "rewrite" => [ "slug" => "rome", "with_front" => true ],
            "query_var" => true,
            "supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields" ],
            "taxonomies" => [ "loactions", "locations_type", "offer" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "rome", $args );
    
        /**
         * Post Type: Florence Tours.
         */
    
        $labels = [
            "name" => esc_html__( "Florence Tours", "custom-post-type-ui" ),
            "singular_name" => esc_html__( "Florence", "custom-post-type-ui" ),
        ];
    
        $args = [
            "label" => esc_html__( "Florence Tours", "custom-post-type-ui" ),
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
            "rewrite" => [ "slug" => "florence", "with_front" => true ],
            "query_var" => true,
            "supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields" ],
            "taxonomies" => [ "loactions", "offer" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "florence", $args );
    
        /**
         * Post Type: Barcelona Tours.
         */
    
        $labels = [
            "name" => esc_html__( "Barcelona Tours", "custom-post-type-ui" ),
            "singular_name" => esc_html__( "Barcelona", "custom-post-type-ui" ),
        ];
    
        $args = [
            "label" => esc_html__( "Barcelona Tours", "custom-post-type-ui" ),
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
            "rewrite" => [ "slug" => "barcelona", "with_front" => true ],
            "query_var" => true,
            "supports" => [ "title", "editor", "thumbnail", "page-attributes" ],
            "taxonomies" => [ "loactions", "offer" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "barcelona", $args );
    }
}

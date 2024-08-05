<?php

namespace CC_RezdyAPI;

class Settings
{

    const COLUMNS = [];

    public static function setupDb(float $db_version = 0)
    {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        //1st Table
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $rezdy_plugin_transactions_sql = "CREATE TABLE `$rezdy_plugin_transactions` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `rezdy_order_id` varchar(255) NULL,
            `transactionID` varchar(255) NULL,
            `success_message` varchar(255) NULL,
            `failure_message` varchar(255) NULL,
            `order_status` varchar(255) NULL,
            `IP_address` varchar(255) NULL,
            `username` varchar(255) NULL,
            `useremail` varchar(255) NULL,
            `firstName` varchar(255) NULL,
            `lastName` varchar(255) NULL,
            `phone` varchar(255) NULL,
            `country` varchar(255) NULL,
            `date_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `response_time` varchar(255) NULL,
            `totalAmount` varchar(255) NOT NULL,
            `totalPaid` varchar(255) NULL,
            `payment_method` varchar(255) NULL,
            `paypal_token` varchar(255) NULL,
            `paypal_payer_id` varchar(255) NULL,
            `rezdy_params` longtext NOT NULL,
            `rezdy_response_params` longtext NULL,
            `rezdy_booking_status` varchar(255) NULL,
            `rezdy_total_amount` varchar(255) NULL,
            `rezdy_total_paid` varchar(255) NULL,
            `rezdy_due_amount` varchar(255) NULL,
            `rezdy_payment_type` varchar(255) NULL,
            `rezdy_created_date` varchar(255) NULL,
            `rezdy_confirmed_date` varchar(255) NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate;";

        if ($wpdb->get_var("SHOW TABLES LIKE '$rezdy_plugin_transactions'") != $rezdy_plugin_transactions) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($rezdy_plugin_transactions_sql);
        }

        //2nd Table
        $razdy_authentication = $wpdb->prefix . 'razdy_authentication';
        $md5EncodedPassword = md5('wsELpm2rDPprLrzxPdYI');
        $razdy_authentication_sql = "CREATE TABLE `$razdy_authentication` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `password` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
          ) $charset_collate;

            INSERT INTO `$razdy_authentication` (`password`) VALUES ('$md5EncodedPassword');
          ";

        if ($wpdb->get_var("SHOW TABLES LIKE '$razdy_authentication'") != $razdy_authentication) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($razdy_authentication_sql);
        }


        //3rd Table
        $add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
        $add_to_cart_data_sql = "CREATE TABLE `$add_to_cart_data` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `sessionID` varchar(255) NOT NULL,
            `sessionData` longtext NULL,
            `postData` longtext NULL,
            PRIMARY KEY (`id`)
          ) $charset_collate;";

        if ($wpdb->get_var("SHOW TABLES LIKE '$add_to_cart_data'") != $add_to_cart_data) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($add_to_cart_data_sql);
        }
    }

    public static function prepareData(array $args): array
    {
    }

    public static function insert(array $args): int
    {
    }

    public static function firstRow()
    {
    }

    public static function insertBulk(array $items): int
    {
    }

    public static function push(string $message): int
    {
    }

    public static function update(int $id, array $args): bool
    {
    }

    public static function delete(array $ids): int
    {
    }

    public static function deleteAll(): int
    {
    }

    public static function delete_Tables_Options()
    {

        global $wpdb;
        $rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
        $razdy_authentication = $wpdb->prefix . 'razdy_authentication';
        $add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
        $wpdb->query("DROP TABLE IF EXISTS $rezdy_plugin_transactions");
        $wpdb->query("DROP TABLE IF EXISTS $razdy_authentication");
        $wpdb->query("DROP TABLE IF EXISTS $add_to_cart_data");
        delete_option("cc:db_version");
        delete_option("cc_stripe_pub_api_key");
        delete_option("cc_stripe_secret_api_key");
        delete_option("cc_success_url");
        delete_option("cc_cancel_url");
        delete_option("cc_rezdy_api_key");
        delete_option("cc_rezdy_api_url");
        delete_option("cc_paypal_client_id");
        delete_option("cc_paypal_secret_api_key");
        delete_option("cc_paypal_live");
        delete_option("cc_picked_color");
        delete_option("cc_stripe_enabled");
        delete_option("cc_paypal_enabled");
        delete_option("cc_airwallex_enabled");
        delete_option("cc_airwallex_client_id");
        delete_option("cc_airwallex_secret_api_key");
        delete_option("cc_airwallex_live");


        setcookie('wordpress_session_custom', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        // Unset the cookie value from the $_COOKIE array
        unset($_COOKIE['wordpress_session_custom']);
        // Optionally, you may also destroy the cookie value from the current session
        if (isset($_SESSION['wordpress_session_custom'])) {
            unset($_SESSION['wordpress_session_custom']);
        }
    }
}

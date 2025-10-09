<?php

namespace CC_RezdyAPI\Admin\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Objects\Image;
use CC_RezdyAPI\Rezdy\Requests\Objects\SessionPriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionBatchUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;
use Exception;

class Settings extends Screen
{
    public function render()
    {
        $session_data = $this->get_session_data();
        $rezdy_auth_pass = $session_data['rezdy_auth_pass'];

        if ( isset( $_GET['manual-update-rates'] ) ) {
            $this->rezdyCurrency->fetch_and_update_currency_rates();
        }

        return $this->renderTemplate('settings.php', [
            'nonce' => wp_create_nonce('cc-rezdy-api'),
            'rezdy_api_key' => get_option('cc_rezdy_api_key'),
            'rezdy_api_url' => get_option('cc_rezdy_api_url'),
            'picked_color' => get_option('cc_picked_color'),
            'default_currency' => get_option('cc_default_currency'),
            'stripe_enabled' => get_option('cc_stripe_enabled'),
            'paypal_enabled' => get_option('cc_paypal_enabled'),
            'airwallex_enabled' => get_option('cc_airwallex_enabled'),
            'googlepay_enabled' => get_option('cc_googlepay_enabled'),
            'stripe_pub_api_key' => get_option('cc_stripe_pub_api_key'),
            'stripe_secret_api_key' => get_option('cc_stripe_secret_api_key'),
            'success_url' => get_option('cc_success_url'),
            'cancel_url' => get_option('cc_cancel_url'),
            'paypal_client_id' => get_option('cc_paypal_client_id'),
            'paypal_secret_api_key' => get_option('cc_paypal_secret_api_key'),
            'paypal_live' => get_option('cc_paypal_live'),
            'airwallex_client_id' => get_option('cc_airwallex_client_id'), // ==== airwallex ==
            'airwallex_secret_api_key' => get_option('cc_airwallex_secret_api_key'), // ==== airwallex ===
            'airwallex_live' => get_option('cc_airwallex_live'), // ==== airwallex ===
            'tapfiliate_api_key' => get_option('cc_tapfiliate_api_key'), ##For Tapfiliate
            'monday_api_key' => get_option('cc_monday_api_key'), // for Monday API Key
            'monday_board_id' => get_option('cc_monday_board_id'), // for Monday Board ID
            'klaviyo_api_key' => get_option('cc_klaviyo_api_key'), // for Klaviyo API Key
            'rezdy_auth_pass' => $rezdy_auth_pass,
        ]);
    }

    public function get_session_data()
    {
        if (isset($_COOKIE['wordpress_session_custom'])) {
            return json_decode(stripslashes($_COOKIE['wordpress_session_custom']), true);
        }
        //return array();
    }

    // public function synch_setting()
    // {
    //     return $this->renderTemplate('synch-setting.php', []);
    // }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_style('cc-rezdy-settings-for-admin', "{$base}src/assets/settings.css", array(), rand(1000, 9999), true);
        wp_enqueue_script('change-password-for-admin', "{$base}src/assets/change-password.js", array(), rand(1000, 9999), true);
    }

    public function update()
    {
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'cc-rezdy-api'))
            return $this->error(__('Invalid request, authorization check failed. Please try again.', 'cc-rezdy-api'));

        if (isset($_POST['rezdy_password']))
            return $this->checkPassword();

        if (isset($_POST['old_password']) && isset($_POST['new_password']))
            return $this->changePassword();

        if (isset($_POST['update_rezdy_settings']))
            return $this->updateSettings();

        return $this->success(__('Changes saved successfully.', 'cc-rezdy-api'));
    }

    public function checkPassword()
    {

        global $wpdb;
        $razdy_authentication = $wpdb->prefix . 'razdy_authentication';
        $hashed_password = $wpdb->get_var("SELECT password FROM $razdy_authentication");



        if (!$rezdy_auth_pass = sanitize_text_field($_POST['rezdy_auth_pass'] ?? ''))
            return $this->error(__('Please enter Password.', 'cc-rezdy-api'));

        if (md5($rezdy_auth_pass) != $hashed_password) {
            return $this->error(__('Invalid Password.', 'cc-rezdy-api'));
        } else {

            $session_data = array(
                'rezdy_auth_pass' => $hashed_password,
                // Add more session data as needed
            );
            setcookie('wordpress_session_custom', json_encode($session_data), time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
            $_COOKIE['wordpress_session_custom'] = json_encode($session_data);
            return;
        }
    }
    public function changePassword()
    {
        global $wpdb;
        $razdy_authentication = $wpdb->prefix . 'razdy_authentication';
        $hashed_password = $wpdb->get_var("SELECT password FROM $razdy_authentication");
        if ($hashed_password != md5($_POST['old_password'])) {
            return $this->error(__('Invalid old password. Please enter correct old password.', 'cc-rezdy-api'));
        } else {
            $data_password = array(
                'password' => md5($_POST['new_password'])
            );
            $wpdb->update($razdy_authentication, $data_password, array('id' => 1));
            return $this->success(__('Password updated successfully.', 'cc-rezdy-api'));
        }
    }
    public function updateSettings()
    {

        if (!isset($_COOKIE['wordpress_session_custom'])) {
            return;
        }

        if (!$rezdy_api_key = sanitize_text_field($_POST['rezdy_api_key'] ?? ''))
            return $this->error(__('Please enter a Rezdy API Key.', 'cc-rezdy-api'));

        if (!$rezdy_api_url = sanitize_text_field($_POST['rezdy_api_url'] ?? ''))
            return $this->error(__('Please enter a Rezdy API URL.', 'cc-rezdy-api'));

        $color_picked = (isset($_POST['theme'])) ? $_POST['theme'] : 'theme-cdt';
        $default_currency = (isset($_POST['default_currency'])) ? $_POST['default_currency'] : 'EUR';



        $stripe_enabled = (isset($_POST['stripe_enabled'])) ? $_POST['stripe_enabled'] : '';
        $paypal_enabled = (isset($_POST['paypal_enabled'])) ? $_POST['paypal_enabled'] : '';
        $airwallex_enabled = (isset($_POST['airwallex_enabled'])) ? $_POST['airwallex_enabled'] : '';
        $googlepay_enabled = (isset($_POST['googlepay_enabled'])) ? $_POST['googlepay_enabled'] : '';

        // Ensure at least one payment gateway is enabled
        if (empty($stripe_enabled) && empty($paypal_enabled) && empty($airwallex_enabled) && empty($googlepay_enabled)) {
            return $this->error(__('At least one payment gateway must be enabled.', 'cc-rezdy-api'));
        }

        //Stripe
        if (!empty($stripe_enabled)) {
            if (!$stripe_pub_api_key = sanitize_text_field($_POST['stripe_pub_api_key'] ?? ''))
                return $this->error(__('Please enter a Stripe Publishable Key.', 'cc-rezdy-api'));

            if (!$stripe_secret_api_key = sanitize_text_field($_POST['stripe_secret_api_key'] ?? ''))
                return $this->error(__('Please enter a Stripe Secret Key.', 'cc-rezdy-api'));
        }

        //PayPal
        if (!empty($paypal_enabled)) {
            $paypal_live = (isset($_POST['paypal_live'])) ? $_POST['paypal_live'] : '';
            if (!$paypal_client_id = sanitize_text_field($_POST['paypal_client_id'] ?? ''))
                return $this->error(__('Please enter a PayPal Client ID.', 'cc-rezdy-api'));

            if (!$paypal_secret_api_key = sanitize_text_field($_POST['paypal_secret_api_key'] ?? ''))
                return $this->error(__('Please enter a PayPal secret key.', 'cc-rezdy-api'));
        }

        // ========== airwallex ========

        //Airwallex
        if (!empty($airwallex_enabled)) {
            $airwallex_live = (isset($_POST['airwallex_live'])) ? $_POST['airwallex_live'] : '';
            if (!$airwallex_client_id = sanitize_text_field($_POST['airwallex_client_id'] ?? ''))
                return $this->error(__('Please enter a Airwallex Client ID.', 'cc-rezdy-api'));

            if (!$airwallex_secret_api_key = sanitize_text_field($_POST['airwallex_secret_api_key'] ?? ''))
                return $this->error(__('Please enter a Airwallex secret key.', 'cc-rezdy-api'));
        }
        // ============== end ==========

        //GooglePay
        if (!empty($googlepay_enabled)) {
            if (!$airwallex_client_id = sanitize_text_field($_POST['airwallex_client_id'] ?? ''))
                return $this->error(__('Please enter a Airwallex Client ID for Google Pay.', 'cc-rezdy-api'));

            if (!$airwallex_secret_api_key = sanitize_text_field($_POST['airwallex_secret_api_key'] ?? ''))
                return $this->error(__('Please enter a Airwallex secret key for Google Pay.', 'cc-rezdy-api'));
        }

        if (!$tapfiliate_api_key = sanitize_text_field($_POST['tapfiliate_api_key'] ?? '')) ##For Tapfiliate
            return $this->error(__('Please enter Tapfiliate API key.', 'cc-rezdy-api'));


        if (!$success_url = sanitize_text_field($_POST['success_url'] ?? ''))
            return $this->error(__('Please enter success url.', 'cc-rezdy-api'));

        if (!$cancel_url = sanitize_text_field($_POST['cancel_url'] ?? ''))
            return $this->error(__('Please enter cancel url.', 'cc-rezdy-api'));

        $monday_api_key = sanitize_text_field($_POST['monday_api_key'] ?? '');
        $monday_board_id = sanitize_text_field($_POST['monday_board_id'] ?? '');

        if (! $monday_api_key) {
            return $this->error(__('Please enter Monday API Key.', 'cc-rezdy-api'));
        }

        if (! $monday_board_id) {
            return $this->error(__('Please enter Monday Board ID.', 'cc-rezdy-api'));
        }

        $klaviyo_api_key = sanitize_text_field($_POST['klaviyo_api_key'] ?? '');

        if (! $klaviyo_api_key) {
            return $this->error(__('Please enter Klaviyo API Key.', 'cc-rezdy-api'));
        }

        update_option('cc_stripe_enabled', $stripe_enabled);
        update_option('cc_paypal_enabled', $paypal_enabled);
        update_option('cc_airwallex_enabled', $airwallex_enabled);
        update_option('cc_googlepay_enabled', $googlepay_enabled);

        update_option('cc_stripe_pub_api_key', $stripe_pub_api_key);
        update_option('cc_stripe_secret_api_key', $stripe_secret_api_key);

        update_option('cc_success_url', $success_url);
        update_option('cc_cancel_url', $cancel_url);

        update_option('cc_rezdy_api_key', $rezdy_api_key);
        update_option('cc_rezdy_api_url', $rezdy_api_url);

        update_option('cc_paypal_client_id', $paypal_client_id);
        update_option('cc_paypal_secret_api_key', $paypal_secret_api_key);

        update_option('cc_paypal_live', $paypal_live);
        // ========== airwallex ========
        update_option('cc_airwallex_client_id', $airwallex_client_id);
        update_option('cc_airwallex_secret_api_key', $airwallex_secret_api_key);
        update_option('cc_airwallex_live', $airwallex_live);
        // ============== end ==========

        // ========== //GooglePay ========
        update_option('cc_airwallex_client_id', $airwallex_client_id);
        update_option('cc_airwallex_secret_api_key', $airwallex_secret_api_key);
        // ============== //GooglePay end ==========

        update_option('cc_tapfiliate_api_key', $tapfiliate_api_key); ##For Tapfiliate

        update_option('cc_monday_api_key', $monday_api_key); // For Monday API Key
        update_option('cc_monday_board_id', $monday_board_id); // For Monday Board ID

        update_option('cc_klaviyo_api_key', $klaviyo_api_key); // For Klaviyo API Key

        update_option('cc_picked_color', $color_picked);
        update_option('cc_default_currency', $default_currency);

        return $this->success(__('Rezdy settings updated successfully.', 'cc-rezdy-api'));
    }
}

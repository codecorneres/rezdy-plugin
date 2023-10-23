<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1><?php _e('CC Rezdy API &lsaquo; Settings', 'cc-rezdy-api'); ?></h1>
    <form method="post" style="margin-top:15px">
        <h3><?php _e('Rezdy Settings', 'cc-rezdy-api'); ?></h3>
        <p><a href="https://developers.rezdy.com/" target="_blank">Rezdy Documentation</a>, <a href="https://app.rezdy-staging.com/#new-terms" target="_blank">Rezdy Login</a></p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="rezdy_api_key"><?php _e('Rezdy API Key', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="rezdy_api_key" value="<?php echo esc_attr($rezdy_api_key); ?>" type="text" id="rezdy_api_key" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rezdy_api_url"><?php _e('Rezdy API URL', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="rezdy_api_url" value="<?php echo esc_attr($rezdy_api_url); ?>" type="text" id="rezdy_api_url" class="regular-text"></td>
                </tr>
            </tbody>
        </table>

        <h3><?php _e('Stripe Settings', 'cc-rezdy-api'); ?></h3>
        <p><a href="https://dashboard.stripe.com/login" target="_blank">Stripe Login</a></p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="stripe_pub_api_key"><?php _e('Stripe Publishable Key', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="stripe_pub_api_key" value="<?php echo esc_attr($stripe_pub_api_key); ?>" type="text" id="stripe_pub_api_key" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stripe_secret_api_key"><?php _e('Stripe Secret Key', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="stripe_secret_api_key" value="<?php echo esc_attr($stripe_secret_api_key); ?>" type="text" id="stripe_secret_api_key" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="success_url"><?php _e('Stripe Success URL', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="success_url" value="<?php echo esc_attr($success_url); ?>" type="text" id="success_url" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cancel_url"><?php _e('Stripe Cancel URL', 'cc-rezdy-api'); ?></label></th>
                    <td><input name="cancel_url" value="<?php echo esc_attr($cancel_url); ?>" type="text" id="cancel_url" class="regular-text"></td>
                </tr>
            </tbody>
        </table>

        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="update_rezdy_settings" value="1">
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'cc-rezdy-api'); ?>">
    </form>
</div>
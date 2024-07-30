<?php defined('ABSPATH') || exit; ?>

<?php if ($rezdy_auth_pass == '') : ?>
    <div class="wrap">
        <form method="post" style="margin-top:15px" onsubmit="return validatePasswordFields()">
            <div class="head_container">
                <h3 class="page_custom_head"><?php _e('Password for Payment details:', 'cc-rezdy-api'); ?></h3>&nbsp;<a href="javascript:void(0)" class="change_pass">Change Password</a>
            </div>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="login_password">
                        <th scope="row"><label for="rezdy_auth_pass"><?php _e('Enter Password', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="rezdy_auth_pass" value="" type="password" id="rezdy_auth_pass" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" class="password_type" name="rezdy_password" value="1">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Submit', 'cc-rezdy-api'); ?>">
        </form>
    </div>
<?php else : ?>
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
                    <tr>
                        <th scope="row"><label for="rezdy_color_picker"><?php _e('Choose Theme', 'cc-rezdy-api'); ?></label></th>
                        <td>
                            <select name="theme" id="themes" class="regular-text">
                                <option value="theme-cdt" <?php if (!empty($picked_color) && $picked_color == 'theme-cdt') echo 'selected="selected"'; ?>>CDT Theme</option>
                                <option value="theme-rwc" <?php if (!empty($picked_color) && $picked_color == 'theme-rwc') echo 'selected="selected"'; ?>>RWC Theme</option>
                                <option value="theme-jtr" <?php if (!empty($picked_color) && $picked_color == 'theme-jtr') echo 'selected="selected"'; ?>>JTR Theme</option>
                                <option value="theme-tipsy" <?php if (!empty($picked_color) && $picked_color == 'theme-tipsy') echo 'selected="selected"'; ?>>Tipsy Theme</option>
                            </select>
                        </td>
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
                        <th scope="row"><label for="stripe_disable"><?php _e('Stripe Disable', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="stripe_disable" value="yes" type="checkbox" id="stripe_disable" class="regular-text" <?php if (isset($stripe_disable) && $stripe_disable == 'yes') echo "checked='checked'"; ?>></td>
                    </tr>
                </tbody>
            </table>

            <h3><?php _e('PayPal Settings', 'cc-rezdy-api'); ?></h3>
            <p><a href="https://www.paypal.com/signin" target="_blank">PayPal Login</a></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <?php //echo esc_attr($paypal_live); exit();
                    ?>
                    <tr>
                        <th scope="row"><label for="paypal_live"><?php _e('PayPal Live account', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="paypal_live" value="yes" type="checkbox" id="paypal_live" class="regular-text" <?php if (isset($paypal_live) && $paypal_live == 'yes') echo "checked='checked'"; ?>></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="paypal_client_id"><?php _e('PayPal Client Id', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="paypal_client_id" value="<?php echo esc_attr($paypal_client_id); ?>" type="text" id="paypal_client_id" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="paypal_secret_api_key"><?php _e('Paypal Secret Key', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="paypal_secret_api_key" value="<?php echo esc_attr($paypal_secret_api_key); ?>" type="text" id="paypal_secret_api_key" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>
            <!-- ========== airwallex ======== -->
            <h3><?php _e('Airwallex Settings', 'cc-rezdy-api'); ?></h3>
            <p><a href="https://airwallex.com/app/login" target="_blank">Airwallex Login</a></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="airwallex_live"><?php _e('Airwallex Live Account', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="airwallex_live" value="yes" type="checkbox" id="airwallex_live" class="regular-text" <?php if (isset($airwallex_live) && $airwallex_live == 'yes') echo "checked='checked'"; ?>></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="airwallex_client_id"><?php _e('Airwallex Client ID', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="airwallex_client_id" value="<?php echo esc_attr($airwallex_client_id); ?>" type="text" id="airwallex_client_id" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="airwallex_secret_api_key"><?php _e('Airwallex Secret Key', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="airwallex_secret_api_key" value="<?php echo esc_attr($airwallex_secret_api_key); ?>" type="text" id="airwallex_secret_api_key" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>
            <!-- ============= end ============ -->

            <h3><?php _e('URL Settings', 'cc-rezdy-api'); ?></h3>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="success_url"><?php _e('Payment Success URL', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="success_url" value="<?php echo esc_attr($success_url); ?>" type="text" id="success_url" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cancel_url"><?php _e('Payment Cancel URL', 'cc-rezdy-api'); ?></label></th>
                        <td><input name="cancel_url" value="<?php echo esc_attr($cancel_url); ?>" type="text" id="cancel_url" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>

            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="update_rezdy_settings" value="1">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'cc-rezdy-api'); ?>">
        </form>
    </div>
<?php endif; ?>
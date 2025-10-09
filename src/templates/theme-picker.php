<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1><?php _e('Theme Picker &lsaquo; Widget', 'cc-rezdy-theme-picker'); ?></h1>
    <form method="post" style="margin-top:15px">
        <h3><?php _e('Pick a theme', 'cc-rezdy-theme-picker'); ?></h3>


        <label for="cars">Themes:</label>
        <select name="theme" id="themes">
            <option value="volvo">Volvo</option>
            <option value="saab">Saab</option>
            <option value="opel">Opel</option>
            <option value="audi">Audi</option>
        </select>

        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="update_rezdy_settings" value="1">
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Theme', 'cc-rezdy-theme-picker'); ?>">
    </form>
</div>
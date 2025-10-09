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

class ThemePicker extends Screen
{

    public function render()
    {
        return $this->renderTemplate('theme-picker.php', []);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        // wp_enqueue_style('cc-rezdy-sync-css', "{$base}src/assets/sync_to_rezdy.css", [], $this->appContext::SCRIPTS_VERSION);
        // wp_enqueue_script('cc-rezdy-sync-js', "{$base}src/assets/sync_to_rezdy.js", [], $this->appContext::SCRIPTS_VERSION);
        // wp_localize_script('cc-rezdy-sync-ajax', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }
}

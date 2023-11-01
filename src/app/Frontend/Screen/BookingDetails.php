<?php

namespace CC_RezdyAPI\Frontend\Screen;

use CC_RezdyAPI\App;
use CC_RezdyAPI\RezdyAPI;

class BookingDetails extends Screen
{

    public function render()
    {
        if (!session_id()) {
            session_start();
        }
        $guzzleClient           = new RezdyAPI('6ac1101abf47440fb7014c8fe378c9d9');
        $rezdy_api_product_code = $_SESSION['form_data']['OrderItem']['productCode'];
        $product                = $guzzleClient->products->get($rezdy_api_product_code);
        // echo "<pre>";
        // print_r($_SESSION['form_data']['OrderItem']['productCode']);
        $this->renderTemplate('booking-details.php', [
            'product' => $product,
            'session' => $_SESSION['form_data']
        ]);
    }

    public function scripts()
    {
        $base = trailingslashit(plugin_dir_url($this->appContext->getPluginFile()));
        wp_enqueue_style('cc-rezdy-api', "{$base}src/assets/includes/css/booking-details-style.css", [], $this->appContext::SCRIPTS_VERSION);
    }
}

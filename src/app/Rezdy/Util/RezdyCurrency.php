<?php

namespace CC_RezdyAPI\Rezdy\Util;

class RezdyCurrency
{
    public static function deactivation()
    {
        $timestamp = wp_next_scheduled( 'update_currency_rates_event' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'update_currency_rates_event' );
        }
    }

    public function setup_currencies_data()
    {
        // Uncomment to manually update currency rates
        // $this->fetch_and_update_currency_rates();
        add_action( 'wp', [$this, 'schedule_currency_update_cron'] );
        add_action( 'update_currency_rates_event', [$this, 'fetch_and_update_currency_rates'] );
        add_filter( 'cron_schedules', [$this, 'add_two_hours_cron_interval'] );
        add_shortcode( 'rezdy_currency_dropdown', [$this, 'rezdy_currency_dropdown_func'] );
    }

    public function setup_currencies_scripts()
    {
        add_action( 'wp_enqueue_scripts', [$this, 'add_currency_conversion_script'] );
    }

    public function fetch_and_update_currency_rates() {
        $url = CURRENCY_API_URL . '?access_key=' . CURRENCY_API_APP_ID;

        // Make the request using wp_remote_get
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            error_log( 'Currency API request failed: ' . $response->get_error_message() );
            return;
        }

        $body = wp_remote_retrieve_body( $response );
        $rates = json_decode( $body );

        error_log(json_encode([
            '$rates' => $rates
        ]));

        if ( isset( $rates->rates ) ) {
            update_option( CURRENCY_OPTION_KEY, $rates->rates );
            error_log( sprintf(
                'Currency rates updated successfully: 1 %s equals %s USD at %s',
                $rates->base,
                $rates->rates->USD,
                date( 'H:i jS F, Y', $rates->timestamp )
            ));
            error_log( json_encode( [
                'rates' => $rates
            ] ) );
        } else {
            error_log( 'Failed to update currency rates: Invalid response.' );
        }
    }

    public function add_two_hours_cron_interval( $schedules ) {
        $schedules['two_hours'] = array(
            'interval' => 8 * 60 * 60, // 8 hours in seconds
            'display'  => __( 'Every 8 Hours' ),
        );
        return $schedules;
    }

    public function schedule_currency_update_cron() {
        if ( ! wp_next_scheduled( 'update_currency_rates_event' ) ) {
            wp_schedule_event( time(), 'two_hours', 'update_currency_rates_event' );
        }
    }

    public function add_currency_conversion_script() {
        // Get PHP variables
        $currency_rates = get_option(CURRENCY_OPTION_KEY);
        $default_currency = get_option('cc_default_currency') ?? 'EUR';
        $rezdy_booking_type = get_option('cc_picked_color');
        $rezdy_booking_selectors = [];

        switch ($rezdy_booking_type) {
            case 'theme-cdt':
                $rezdy_booking_selectors = ['.icon_price .price_exprnce', '.product .form-right_tour .booktour_popup > h2'];
                break;
            case 'theme-rwc':
                $rezdy_booking_selectors = ['.class-loop-prices div .elementor-heading-title'];
                break;
            case 'theme-jtr':
                $rezdy_booking_selectors = ['.class-loop-prices div .elementor-heading-title', '.product-sticky-price .elementor-heading-title'];
                break;
            case 'theme-tipsy':
                $rezdy_booking_selectors = ['.gift-card-row .gift-amount'];
                break;
            default:
                break;
        }

        // Enqueue the script
        wp_enqueue_script(
            'rezdy-currency-conversion',
            PLUGIN_URL . 'assets/includes/js/rezdy-currency-conversion.js',
            ['jquery'],
            rand(111111, 999999),
            true
        );

        wp_localize_script('rezdy-currency-conversion', 'currencyData', [
            'rates' => $currency_rates,
            'baseCurrency' => $default_currency,
            'selectors' => $rezdy_booking_selectors,
        ]);
    }

    public function rezdy_currency_dropdown_func() {
        ob_start();
        $template = PLUGIN_DIR_PATH . 'templates/rezdy-currency-dropdown.php';
        if (file_exists($template)) {
            include $template;
        }
        return ob_get_clean();
    }

    public function convert_paypal_rates($data)
    {
        error_log(json_encode([
            'paypal_data_convert' => $data
        ]));
        $purchaseUnits = $data['purchase_units'];
        $newPurchaseUnits = [];
        $currencySymbolText = get_symbol_as_text();
        $itemsTotal = 0;

        foreach ($purchaseUnits as $purchaseUnit) {
            $purchaseUnitItems = $purchaseUnit['items'];
            $purchaseUnitAmount = $purchaseUnit['amount'];
            $newPurchaseUnitItems = [];
            $newPurchaseUnitAmount = null;

            foreach ($purchaseUnitItems as $purchaseUnitItem) {
                $unitAmountValue = $purchaseUnitItem['unit_amount']['value'];
                $unitAmountQuantity = $purchaseUnitItem['quantity'];

                $purchaseUnitItem['unit_amount']['currency_code'] = $currencySymbolText;
                $purchaseUnitItem['unit_amount']['value'] = convert_currency_only($unitAmountValue, true);

                $itemsTotal = $itemsTotal + (convert_currency_only($unitAmountValue, true) * $unitAmountQuantity);
                $newPurchaseUnitItems[] = $purchaseUnitItem;
            }

            $amountValue = $purchaseUnitAmount['value'];
            $purchaseUnitAmount['currency_code'] = $currencySymbolText;
            $purchaseUnitAmount['value'] = convert_currency_only($amountValue, true);
            $purchaseUnitAmount['breakdown']['item_total']['currency_code'] = $currencySymbolText;
            $purchaseUnitAmount['breakdown']['item_total']['value'] = format_the_amount($itemsTotal);

            if (isset($purchaseUnitAmount['breakdown']['discount'])) {
                $amountBreakDiscountValue = $purchaseUnitAmount['breakdown']['discount']['value'];
                $purchaseUnitAmount['breakdown']['discount']['currency_code'] = $currencySymbolText;
                $purchaseUnitAmount['breakdown']['discount']['value'] = convert_currency_only($amountBreakDiscountValue, true);

                // check if the discounted amount and amount doesn't add up to the balance
                $unitAmount = $purchaseUnitAmount['value'];
                $unitAmountTotalValue = $purchaseUnitAmount['breakdown']['item_total']['value'];
                $unitAmountDiscountValue = $purchaseUnitAmount['breakdown']['discount']['value'];
                $sumOfDiscount = number_format($unitAmount + $unitAmountDiscountValue, 2, '.', '');

                error_log(json_encode([
                    '$unitAmount' => $unitAmount,
                    '$unitAmountTotalValue' => $unitAmountTotalValue,
                    '$unitAmountDiscountValue' => $unitAmountDiscountValue,
                    '$sumOfDiscount' => $sumOfDiscount,
                    'isNotEquals' => $sumOfDiscount != $unitAmountTotalValue,
                    'isGreater' => $sumOfDiscount > $unitAmountTotalValue,
                    'isLess' => $sumOfDiscount < $unitAmountTotalValue,
                    '$itemsTotal' => $itemsTotal
                ]));

                if ($sumOfDiscount != $unitAmountTotalValue) {
                    if ($sumOfDiscount > $unitAmountTotalValue) {
                        $excessAmount = $sumOfDiscount - $unitAmountTotalValue;
                        $purchaseUnitAmount['breakdown']['discount']['value'] = number_format($unitAmountDiscountValue - $excessAmount, 2, '.', 0);
                    } else {
                        $missingAmount = $unitAmountTotalValue - $sumOfDiscount;
                        $purchaseUnitAmount['breakdown']['discount']['value'] = number_format($unitAmountDiscountValue + $missingAmount, 2, '.', 0);
                    }
                }

            } else {
                $purchaseUnitAmount['value'] = format_the_amount($itemsTotal);
            }

            $newPurchaseUnitAmount = $purchaseUnitAmount;
            $newPurchaseUnits[] = [
                'items' => $newPurchaseUnitItems,
                'amount' => $newPurchaseUnitAmount,
            ];
        }

        $data['purchase_units'] = $newPurchaseUnits;

        return $data;
    }

    public function convert_airwaller_rates($data)
    {
        $currencySymbolText = get_symbol_as_text();

        $amount = $data['amount'];
        $data['amount'] = convert_currency_only($amount, true);
        $data['currency'] = $currencySymbolText;

        $orderProducts = $data['order']['products'];
        $newOrderProducts = [];

        foreach ($orderProducts as $orderProduct) {
            $unitPrice = $orderProduct['unit_price'];
            $orderProduct['unit_price'] = convert_currency_only($unitPrice, true);
            $newOrderProducts[] = $orderProduct;
        }

        $data['order']['products'] = $newOrderProducts;
        return $data;
    }
}
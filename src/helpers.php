<?php

if (! function_exists('console_log')) {
    /**
     * Do a simple console log
     *
     * @param $data
     */
    function console_log($data) {
        echo sprintf('<script>console.log(%s)</script>', json_encode($data));
    }
}

if (! function_exists('check_and_start_session')) {
    /**
     * Check if no session is set then start a new one
     */
    function check_and_start_session() {
        // Check if no session is active
        if (session_status() === PHP_SESSION_NONE) {
            // Start session
            session_start();
        }
    }
}

if (! function_exists('data_get')) {
    /**
     * Get the value of a key from an array/object
     *
     * @param $data
     * @param $key
     * @param $default
     */
    function data_get($data, $key, $default = null) {
        if (empty($key)) {
            return $default;
        }

        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (is_array($data) && isset($data[$segment])) {
                $data = $data[$segment];
            } elseif (is_object($data) && isset($data->$segment)) {
                $data = $data->$segment;
            } else {
                return $default;
            }
        }

        return $data;
    }
}

if (! function_exists('convert_currency')) {
    /**
     * Convert the amount and currency symbol
     *
     * @param $amount
     * @param $isReverse
     * @param $isAmountOnly
     */
    function convert_currency($amount, $isReverse = false, $isAmountOnly = false) {
        $rates = get_option(CURRENCY_OPTION_KEY);
        $base_currency = 'EUR';
        // $selected_currency = isset($_COOKIE['rezdy_selected_currency']) ? $_COOKIE['rezdy_selected_currency'] : 'EUR';
        $selected_currency = $_COOKIE['rezdy_selected_currency'] ?? get_option( 'cc_default_currency' ) ?? 'EUR';

        $currency_signs = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];
        $currency_sign = $currency_signs[$selected_currency] ?? '€';

        if ($selected_currency == 'EUR') {
            if ($isAmountOnly) {
                return $amount;
            }
            return [$currency_sign, $amount];
        }

        if (! $rates || ! isset($rates) || ! is_object($rates)) {
            return [$currency_sign, $amount];
        }

        if (! isset($rates->$selected_currency)) {
            return [$currency_sign, $amount];
        }

        $convertedAmount = $amount;

        if ($base_currency !== $selected_currency) {
            if (! $isReverse) {
                $convertedAmount = $amount * $rates->$selected_currency;
            } else {
                $convertedAmount = $amount / $rates->$selected_currency;
            }
        }

        // $convertedAmount = $isReverse ? round($convertedAmount, 2) : number_format($convertedAmount, 2, '.', '');
        $convertedAmount = round($convertedAmount, 2);
        $formattedAmount = ($convertedAmount == floor($convertedAmount))
            ? number_format($convertedAmount, 0)
            : number_format($convertedAmount, 2);

        if ($isAmountOnly) {
            return $formattedAmount;
        }

        return [
            $currency_sign,
            $formattedAmount
        ];
    }
}

if (! function_exists('change_symbol_to_text')) {
    /**
     * Change the currency symbol to text
     *
     * @param $symbol
     * @param $textOnly
     */
    function change_symbol_to_text($symbol, $textOnly = false) {
        $currencySymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];

        foreach ($currencySymbols as $currencyCode => $currencySymbol) {
            if ($currencySymbol === $symbol) {
                if ($textOnly) {
                    return $currencyCode;
                }
                return "Price($currencyCode)";
            }
        }

        return "Price(EUR)";
    }
}

if (! function_exists('get_symbol_as_text')) {
    /**
     * Get the current currency symbol as text
     */
    function get_symbol_as_text() {
        $currency = convert_currency(0);
        return change_symbol_to_text($currency[0], true);
    }
}

if (! function_exists('convert_currency_only')) {
    /**
     * Convert the amount currency and as an amount only
     *
     * @param $amount
     */
    function convert_currency_only($amount, $formatNumber = false) {
        $currency = convert_currency($amount);

        if ($formatNumber) {
            return format_the_amount($currency[1]);
        }
        return $currency[1];
    }
}

if (! function_exists('format_the_amount')) {
    /**
     * Format the amount by one or two decimal places
     *
     * @param $amount
     */
    function format_the_amount($amount) {
        if (round($amount, 2) == $amount) {
            return number_format($amount, 2);
        } else {
            return number_format(round($amount, 1), 1);
        }
    }
}

if ( ! function_exists( 'read_local_data' ) ) {
    /**
     * Read local JSON data from the plugin's src/data directory
     *
     * @param string $fileName The name of the JSON file to read
     * @return array|null The decoded JSON data or null if the file does not exist
     */
    function read_local_data( $fileName ) {
        $pluginDir = plugin_dir_path( __DIR__ );
        $jsonFilePath = $pluginDir . 'src/data/' . $fileName;

        if ( file_exists( $jsonFilePath ) ) {
            $jsonContent = file_get_contents( $jsonFilePath );
            $data = json_decode( $jsonContent, true );
            return $data;
        } else {
            error_log( "JSON file not found at $jsonFilePath" );
            return null;
        }
    }
}

if ( ! function_exists( 'custom_json_log' ) ) {
	/**
	 * Logs a variable number of parameters as a single JSON string to the debug.log file.
	 *
	 * @param string $title A title or description for the log entry.
	 * @param mixed ...$args Any number of parameters (strings, arrays, objects, etc.) to be logged.
	 */
	function custom_json_log( $title, ...$args ) {
		if ( empty( $args ) ) {
			return;
		}

		$log_entry = [
			'timestamp' => date( DateTime::ATOM ),
			'data'      => count( $args ) === 1 ? $args[0] : $args,
		];

		$json_string = json_encode( $log_entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        error_log( $title );

		if ( false === $json_string ) {
			error_log( '--- JSON Encoding Failed: ' . json_last_error_msg() . ' ---' );
			error_log( print_r( $args, true ) );
		} else {
			error_log( $json_string );
		}
	}
}

if ( ! function_exists( 'get_airwallex_base_url' ) ) {
    /**
     * Get the base URL for the Airwallex API
     *
     * @return string
     */
    function get_airwallex_base_url() {
        $airwallex_live = get_option( 'cc_airwallex_live' );

        return ( $airwallex_live == 'yes' )
            ? 'https://api.airwallex.com/api/v1/'
            : 'https://api-demo.airwallex.com/api/v1/';
    }
}

if ( ! function_exists( 'get_valid_klarna_country' ) ) {
    /**
     * Get the valid Klarna country code or return a default value
     *
     * @param string $countryCode The country code to validate
     * @param string $default The default country code to return if the provided one is invalid
     * @return string
     */
    function get_valid_klarna_country($countryCode, $default = 'FR') {
        $supportedCountries = [
            'AT', 'BE', 'CH', 'CZ', 'DE', 'DK', 'ES', 'FI', 'FR', 'GB',
            'GR', 'IE', 'IT', 'NL', 'NO', 'PL', 'PT', 'SE', 'US', 'NZ'
        ];

        $countryCode = strtoupper(trim($countryCode));

        return in_array($countryCode, $supportedCountries) ? $countryCode : $default;
    }
}

if ( ! function_exists( 'public_is_klarna_available' ) ) {
    /**
     * Check if Klarna is available for the given product code
     *
     * @param string $productCode The product code to check
     * @return bool
     */
    function public_is_klarna_available($productCode)
    {
        $enabled = [];
        $origin = get_option('cc_picked_color') ?? 'cdt';
        $originKlarnaLists = read_local_data('product_klarna.json');

        foreach ($originKlarnaLists as $originKlarnaList) {
            if ($originKlarnaList['origin'] == $origin) {
                $lists = $originKlarnaList['lists'];

                foreach ($lists as $list) {
                    $tours = $list['tours'];
                    $is_enabled = data_get($list, 'klarna_enabled', true);

                    if (in_array($productCode, $tours)) {
                        $enabled[] = $is_enabled;
                    }
                }
            }
        }

		return false;

        return ! in_array(false, $enabled);
    }
}

if ( ! function_exists( 'get_private_booking_tab' ) ) {
    /**
     * Get the private booking tab iframe or shortcode
     *
     * @param string $private_tutor_page The URL of the form to embed
     * @param int|null $post_id The post ID to get the form source URL from ACF
     * @return string The HTML iframe or embed code
     */
    function get_private_booking_tab( $private_tutor_page = '', $post_id = null )
    {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( empty( $private_tutor_page ) ) {
            $private_tutor_page = get_field( 'form_source_url', $post_id );
        }

        $rezdyTheme = get_option('cc_picked_color') ?? 'theme-cdt';
        $private_tutor_page = convert_monday_url_to_embed( $private_tutor_page );
        // $private_tutor_page = 'https://romewithchef.com/cook/trastevere-rome-food-tour/?dev-mode'; // For testing purposes
        $default_iframe = '<iframe src="https://forms.monday.com/forms/embed/a8d10641240039aafb65d18294ea80f2?r=use1" width="100%" height="1000" frameborder="0" style="max-height: 1200px;"></iframe>';

        // separate setup for JTR
        if ( $rezdyTheme === 'theme-jtr' ) {
            $embed_code = get_field( 'private_booking_code', $post_id );
            // $embed_code = '[rezdy_booking_form productcode="PNSKM6"]'; // For testing purposes

            if ( ! empty( $embed_code ) ) {
                if ( strpos( $embed_code, '[rezdy_booking_form' ) === 0 ) {

                    preg_match_all( '/(\w+)="([^"]*)"/', $embed_code, $matches, PREG_SET_ORDER );
                    $atts = [];
                    foreach ( $matches as $match ) {
                        $atts[$match[1]] = $match[2];
                    }

                    if ( ! isset( $atts['action_type'] ) ) {
                        $embed_code = rtrim( $embed_code, ']' ) . ' action_type="private-tab"]';
                    }

                    // return do_shortcode( $embed_code );

                    $iframe_url = home_url( '/?dev-mode&private_booking_rezdy=' . $atts['productcode'] );
                    return '<iframe src="' . esc_url( $iframe_url ) . '" width="100%" frameborder="0" style="max-height: 1200px;" id="private-booking-widget-frame"></iframe>';
                }

                if ( filter_var( $embed_code, FILTER_VALIDATE_URL ) ) {
                    $embed_host = parse_url( $embed_code, PHP_URL_HOST );
                    $embed_code = convert_monday_url_to_embed( $embed_code );

                    if ( $embed_host === 'forms.monday.com' ) {
                        return '<iframe src="' . esc_url( $embed_code ) . '" width="100%" height="1000" frameborder="0" style="max-height: 1200px;"></iframe>';
                    }
                }

                return do_shortcode( $embed_code );
            }
        }

        if ( $private_tutor_page ) {
            $parsed_url = parse_url( $private_tutor_page );
            $host = $parsed_url['host'] ?? '';

            if ( $host === 'forms.monday.com' ) {
                return '<iframe src="' . esc_url( $private_tutor_page ) . '" width="100%" height="1000" frameborder="0" style="max-height: 1200px;"></iframe>';
            } elseif ( strpos( $host, $_SERVER['HTTP_HOST'] ) !== false ) {
                $linked_post_id = url_to_postid( $private_tutor_page );
                $bookingCodeKey = 'booking_code';

                switch ($rezdyTheme) {
                    case 'theme-cdt':
                        $bookingCodeKey = 'booking_code';
                        break;
                    case 'theme-rwc':
                        $bookingCodeKey = 'booking_code'; // Temporary value
                        break;
                    case 'theme-jtr':
                        $bookingCodeKey = 'booking_code'; // Temporary value
                        break;
                    case 'theme-tipsy':
                        $bookingCodeKey = 'tour_widgets_booking_widget';
                        break;
                    default:
                        break;
                }

                if ( $linked_post_id ) {
                    $embed_code = get_field( $bookingCodeKey, $linked_post_id );
                    // $embed_code = '[rezdy_booking_form productcode="PNSKM6"]'; // For testing purposes

                    if ( ! empty( $embed_code ) ) {
                        if ( strpos( $embed_code, '[rezdy_booking_form' ) === 0 ) {

                            preg_match_all( '/(\w+)="([^"]*)"/', $embed_code, $matches, PREG_SET_ORDER );
                            $atts = [];
                            foreach ( $matches as $match ) {
                                $atts[$match[1]] = $match[2];
                            }

                            if ( ! isset( $atts['action_type'] ) ) {
                                $embed_code = rtrim( $embed_code, ']' ) . ' action_type="private-tab"]';
                            }

                            // return do_shortcode( $embed_code );

                            $iframe_url = home_url( '/?dev-mode&private_booking_rezdy=' . $atts['productcode'] );
                            return '<iframe src="' . esc_url( $iframe_url ) . '" width="100%" height="1000" frameborder="0" style="max-height: 1200px;" id="private-booking-widget-frame"></iframe>';
                        }

                        if ( filter_var( $embed_code, FILTER_VALIDATE_URL ) ) {
                            $embed_host = parse_url( $embed_code, PHP_URL_HOST );
                            $embed_code = convert_monday_url_to_embed( $embed_code );

                            if ( $embed_host === 'forms.monday.com' ) {
                                return '<iframe src="' . esc_url( $embed_code ) . '" width="100%" height="1000" frameborder="0" style="max-height: 1200px;"></iframe>';
                            }
                        }

                        return do_shortcode( $embed_code );
                    }
                }
            }
        }

        return $default_iframe;
    }
}

if ( ! function_exists( 'convert_monday_url_to_embed' ) ) {
    /**
     * Converts a standard Monday form URL to an embeddable version
     *
     * @param string $url The original Monday.com form URL
     * @return string The embed-ready URL
     */
    function convert_monday_url_to_embed( $url ) {
        if ( empty( $url ) || strpos( $url, 'forms.monday.com' ) === false ) {
            return $url;
        }

        $parsed_url = parse_url( $url );
        $host = $parsed_url['host'] ?? '';
        $path = $parsed_url['path'] ?? '';
        $query = $parsed_url['query'] ?? '';

        if ( $host !== 'forms.monday.com' ) {
            return $url;
        }

        if ( strpos( $path, '/forms/embed/' ) === false ) {
            $path = str_replace( '/forms/', '/forms/embed/', $path );
        }

        $embed_url = 'https://forms.monday.com' . $path;

        if ( ! empty( $query ) ) {
            $embed_url .= '?' . $query;
        }

        return $embed_url;
    }
}
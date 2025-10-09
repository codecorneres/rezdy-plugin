<?php

get_header();

?>

<style>
    header, footer, #chat-widget-container, #wpadminbar, .kl-teaser-QS7ezP, .kl-teaser-WvQUmj {
        display: none !important;
    }

    html, .booking-sidebar-widget-box {
        margin-top: 0 !important;
    }

    [class*="klaviyo"],
    [class*="kl-"],
    [id*="klaviyo"] {
        display: none !important;
        pointer-events: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
	
	.booking-sidebar-widget-box.calendar-widget.theme-tipsy {
		border-top-left-radius: 0;
		border-top-right-radius: 0;
	}

    .private-booking-isolated-page .ast-container {
        max-width: 100% !important;
		padding: 0 !important;
    }

    .private-booking-isolated-page .booking-sidebar-widget-box {
        width: 100% !important;
        background-color: #362E2E !important;
		padding: 28px !important;
		border-bottom-left-radius: 20px;
		border-bottom-right-radius: 20px;
    }
	
	@media (min-width: 768px) {
		.private-booking-isolated-page .booking-sidebar-widget-box {
			padding: 38px 30px 26px !important;
		}
	}
</style>

<?php
$code = get_query_var( 'private_booking_rezdy' );
if ( $code ) {
    echo do_shortcode( '[rezdy_booking_form productcode="' . esc_attr( $code ) . '" action_type="private-tab"]' );
}

get_footer();
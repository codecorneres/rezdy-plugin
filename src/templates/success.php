<?php defined('ABSPATH') || exit; ?>

<?php
global $wp_query, $wpdb;

$transactionID = $wp_query->query_vars['transactionID'];
//echo do_shortcode('[success_shortcode]');
$table_name = $wpdb->prefix . 'rezdy_plugin_transactions';

$query = $wpdb->prepare(
    "SELECT * FROM $table_name WHERE transactionID = %s",
    $transactionID
);
$results = $wpdb->get_results($query);

$tourName = array();
foreach ($results as $result) :
    $status = $result->rezdy_booking_status;
    $items = json_decode($result->rezdy_response_params, true);
    foreach ($items as $item) :
        $tourName[] = $item['productName'] . " " . $item['startTimeLocal'];
    endforeach;
endforeach;

$tourNameString = implode(", ", $tourName);

$bookingStatus = ($status == 'PENDING_CUSTOMER') ? 'Pending' : 'Success';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $bookingStatus; ?> Page</title>
    <!-- Add your CSS styles here -->
    <style>
        .success_container {
            max-width: 800px;
            margin: 170px auto 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .successh1 {
            color: #333;
            text-align: center;
        }

        .successp {
            color: #666;
            font-size: 16px;
        }

        .success-message {
            color: #4CAF50;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        @media(max-width:767px) {
            .successh1 {
                font-size: 75px;
                line-height: 1.5;
            }
        }

        @media(max-width:575px) {
            .successh1 {
                font-size: 56px;
            }

            .success_container {
                margin-top: 120px;
            }

            .success-message h4 {
                font-size: 18px;
                line-height: 1.5;
            }
        }
    </style>
</head>

<body>
    <div class="success_container">
        <h1 class="successh1"><?php echo $bookingStatus; ?>!</h1>
        <div class="success-message">
            <?php
            if ($bookingStatus == 'Pending') {
            ?>
                <p class="successp">Your Booking status was pending for
                <h4><?php echo $tourNameString; ?></h4>
                </p>
                <p class="successp">We'll confirm the booking after payment status will be change to confirmed!</p>
            <?php
            } else {
            ?>
                <p class="successp">Your Booking was successful for
                <h4><?php echo $tourNameString; ?></h4>
                </p>
                <p class="successp">Thank you for your purchase!</p>
            <?php
            }
            ?>

        </div>
        <!-- You can include additional content or links here -->
    </div>
    
</body>

</html>
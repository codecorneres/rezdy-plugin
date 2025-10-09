<?php defined('ABSPATH') || exit; ?>
<?php get_header(); ?>

<?php

$defaultError = 'Something went wrong with your payment details!!';  //store total price
global $wpdb;
#Get Add to cart data
$cookie_name = "CUSTOMSESSIONID";
$session_id = $_COOKIE[$cookie_name];

$_ARRAY_SESSION = array();
$table_add_to_cart_data = $wpdb->prefix . 'add_to_cart_data';
$query = $wpdb->prepare(
    "SELECT * FROM $table_add_to_cart_data WHERE sessionID = %s",
    $session_id
);
$results = $wpdb->get_results($query);
if ($results && count($results) === 1) {
    $row = $results[0];
    $_ARRAY_SESSION[] = json_decode($row->sessionData, true);
}



$inserted_id  = $_ARRAY_SESSION[0]['inserted_id'];


#Get Error as per inserted ID
$table_rezdy_plugin_transactions = $wpdb->prefix . 'rezdy_plugin_transactions';
$query = $wpdb->prepare(
    "SELECT * FROM $table_rezdy_plugin_transactions WHERE id = %s",
    $inserted_id
);
$results = $wpdb->get_results($query);
if ($results && count($results) === 1) {
    $row = $results[0];
    $failure_message = $row->failure_message;  //store total price
}





?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Page</title>
    <!-- Add your CSS styles here -->
    <style>
        main#content {
            display: none;
        }

        .error_container {
            max-width: 800px;
            margin: 170px auto 50px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .errorh1 {
            color: #333 !important;
            text-align: center;
        }

        .errorp {
            color: #666;
            font-size: 16px;
        }

        .errorp-danger {
            color: red !important;
            font-size: 16px;
        }

        .error-message {
            color: #4CAF50;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        @media(max-width:767px) {
            .errorh1 {
                font-size: 75px;
                line-height: 1.5;
            }
        }

        @media(max-width:575px) {
            .errorh1 {
                font-size: 56px;
            }

            .error_container {
                margin-top: 120px;
            }

            .error-message h4 {
                font-size: 18px;
                line-height: 1.5;
            }
        }
    </style>
</head>

<body>
    <div class="error_container">
        <h1 class="errorh1">Failed!</h1>
        <div class="error-message">
            <p class="errorp">Your transaction was failed.</p>
            <p class="errorp-danger"><?php echo ($failure_message) ? $failure_message : $defaultError;  //store total price 
                                        ?></p>
        </div>
        <!-- You can include additional content or links here -->
    </div>
</body>

</html>


<?php get_footer(); ?>
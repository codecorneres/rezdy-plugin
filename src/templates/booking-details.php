<?php defined('ABSPATH') || exit; ?>

<?php get_header(); ?>

<?php


global $wpdb;

$_ARRAY_SESSION = array();

if ($session_id == '') {
    wp_redirect(home_url());
    exit();
} else {
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
}



if (!empty($response)) {
    foreach ($response as $key => $value) {
        if (!isset($value['productCode'])) {
            unset($_ARRAY_SESSION[0][$session_id][$key]);
        }

        if ($value['totalPrice'] == 0 && $value['totalQuantity'] == 0) {
            unset($_ARRAY_SESSION[0][$session_id][$key]);
        }
    }
}


if (isset($_ARRAY_SESSION[0]) && !empty($_ARRAY_SESSION[0]) &&  isset($_ARRAY_SESSION[0][$session_id]) && !empty($_ARRAY_SESSION[0][$session_id])) {
    global $wp_query;

    $checkout_id = $wp_query->query_vars['checkout_id'];

    if (isset($_ARRAY_SESSION[0]['codeData']['productCount']) && !empty($_ARRAY_SESSION[0]['codeData']['productCount'])) {
        if ($_ARRAY_SESSION[0]['codeData']['productCount'] != count($_ARRAY_SESSION[0][$session_id])) {
            unset($_ARRAY_SESSION[0]['voucherCode']);
            unset($_ARRAY_SESSION[0]['couponCode']);
            unset($_ARRAY_SESSION[0]['codeData']);
        }
    }
} else {
    wp_redirect(home_url());
    exit();
}






##Update sessionData
$session_data_to_update = array(
    'sessionData' => json_encode($_ARRAY_SESSION[0])
);
$where = array(
    'sessionID' => $session_id,
);
$wpdb->update($table_add_to_cart_data, $session_data_to_update, $where);



function getGroupValue($value)
{
    preg_match_all('/\d+/', $value, $matches);
    $group = $matches[0];
    $selectQuantityOptions = array();
    $selectQuantityOptions[] = 0;
    if (count($group) === 1) {
        $selectQuantityOptions[] = intval($group[0]);
        return $selectQuantityOptions;
    } else if (count($group) === 2) {
        for ($x = intval($group[0]); $x <= intval($group[1]); $x++) {

            $selectQuantityOptions[] = $x;
        }
        return $selectQuantityOptions;
    }
}
?>
<input type="hidden" class="checkout_id" value="<?php echo $checkout_id; ?>">
<input type="hidden" class="session_id" value="<?php echo $session_id; ?>">
<div class="<?php echo (!empty(get_option('cc_picked_color'))) ? get_option('cc_picked_color') : 'theme-cdt'; ?>">
    <div class="loading_dot">Loading&#8230;</div>
    <form class="booking-checkout">
        <div class="container">
            <div class="button" id="booking_checkout_container">
                <a href="<?php echo home_url(); ?>" target="_self" id="make_another_booking" onclick="makeAnotherBooking(this)">Make another booking</a>
            </div>

            <div class="checkout-details">
                <div class="checkout-block" id="checkout_block_id">
                    <div id="error-message"></div>
                    <!-- 1st -->
                    <div class="checkout">
                        <h3 class="mb">
                            <small>Step 1 of 3</small>
                            Booking Details
                        </h3>
                        <?php $counter = 1;
                        $totalPrice = 0; ?>
                        <?php foreach ($response as $k => $detail) : ?>
                            <div class="form-wrapper tour_<?= $k; ?>_schedule_time">

                                <strong><?= $detail['name']; ?></strong>
                                <input type="hidden" value="<?= $detail['productCode']; ?>" name="order[<?= $k; ?>][product_code]">
                                <small class="first">
                                    <small class="two">
                                        <input type="hidden" value="<?= $detail['sessionDate']; ?>" name="order[<?= $k; ?>][sessionDate]">
                                        Date:&nbsp;<?= $detail['sessionDate']; ?>
                                    </small>
                                </small>
                                <small class="third">
                                    <a href="javascript:void(0)" class="pointer editbooking" data-target="edit-<?= $k; ?>" onclick="editbooking(this)">Edit Booking</a>
                                </small>

                            </div>

                            <!-- Edit Booking toggle-->
                            <div class="order-edit-item edit-<?= $k; ?>" style="display:none;">

                                <fieldset class="no-legend edit_booking_<?= $detail['productCode']; ?>_<?= $detail['schedule_time']; ?>">
                                    <input type="hidden" class="quantityRequiredMax" value="<?= (!empty($detail['quantityRequiredMax'])) ? $detail['quantityRequiredMax'] : ''; ?>" />
                                    <?php foreach ($detail['priceOptions'] as $key => $options) { ?>
                                        <div class="form-flex">
                                            <div class="label-box">
                                                <input type="hidden" name="ItemQuantity[<?= $detail['productCode']; ?>][<?= $key; ?>][priceOption][id]" value="<?= $options['priceOptionID']; ?>">
                                                <h6><?php echo ($options['label'] == 'Quantity') ? 'Everyone' : $options['label']; ?></h6>
                                                <p class="price" data-currency-base="" data-original-amount="<?php echo $options['price']; ?>"><?php echo '€' . $options['price']; ?></p>
                                            </div>
                                            <div class="options-box">
                                                <?php if ($options['quantity'] > 20 && !str_contains($options['label'], 'Group')) : ?>

                                                    <input type="number" name="ItemQuantity[<?= $options['priceOptionID']; ?>][<?= $key; ?>][quantity]" class="checkout-quantity-input" value="<?php echo $options['quantity']; ?>" onkeyup="updateInputValue(this)">

                                                <?php else : ?>

                                                    <?php if (str_contains($options['label'], 'Group')) : $result = getGroupValue($options['label']); ?>
                                                        <select name="ItemQuantity[<?= $options['priceOptionID']; ?>][<?= $key; ?>][quantity]" class="checkout_quantity">
                                                            <?php foreach ($result as  $r) : ?>
                                                                <option value="<?php echo $r; ?>" <?php if ($options['quantity'] == $r) echo 'selected="selected"'; ?>><?php echo $r; ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="number" name="" class="checkout-quantity-input" style="display: none;" onkeyup="updateInputValue(this)">
                                                    <?php else : ?>
                                                        <select name="ItemQuantity[<?= $options['priceOptionID']; ?>][<?= $key; ?>][quantity]" class="checkout_quantity" onchange="checkout_quantity(this)">
                                                            <?php if (!empty($detail['quantityRequiredMax']) && $detail['quantityRequiredMax'] <= 20) : ?>
                                                                <?php for ($i = 0; $i <= $detail['quantityRequiredMax']; $i++) : ?>
                                                                    <option value="<?php echo $i; ?>" <?php if ($options['quantity'] == $i) echo 'selected="selected"'; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            <?php else : ?>
                                                                <?php for ($i = 0; $i <= 20; $i++) : ?>
                                                                    <option value="<?php echo $i; ?>" <?php if ($options['quantity'] == $i) echo 'selected="selected"'; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                                <option value="21" data-value="21">>20</option>
                                                            <?php endif; ?>

                                                        </select>
                                                        <input type="number" name="" class="checkout-quantity-input" style="display: none;" onkeyup="updateInputValue(this)">
                                                    <?php endif; ?>


                                                <?php endif; ?>

                                            </div>


                                        </div>
                                    <?php  } ?>

                                    <div class=" update-container">
                                        <input name="btn-update-item" class="update-item btn fl mt-sm mb-sm" type="button" value="Update" data-product-code="<?= $detail['productCode']; ?>" data-schedule-time="<?= $detail['schedule_time']; ?>" data-session-date="<?= $detail['sessionDate']; ?>" onclick="updateItem(this)">
                                    </div>
                                </fieldset>

                            </div>

                            <!-- form-data -->
                            <?php for ($i = 0; $i < $detail['totalQuantity']; $i++) : ?>
                                <fieldset class="Billing_Contact participant_details tour_<?= $k; ?>_schedule_time" style="--feild-number: '<?= $i + 1 ?>';">
                                    <legend class="toggle">Participant</legend>
                                    <div class="content">
                                        <div class="first">
                                            <label for="fname">First Name</label>

                                            <input class="fields participant_firstname" type="text" id="fname" name="participant[<?= $k; ?>][<?= $i; ?>][first_name]" onchange="participantFirstname(this)">
                                        </div>
                                        <div class="last">
                                            <label for="lname">Last Name</label>
                                            <input class="fields participant_lastname" type="text" id="lname" name="participant[<?= $k; ?>][<?= $i; ?>][last_name]" onchange="participantLastname(this)">
                                        </div>
                                    </div>
                                </fieldset>
                            <?php endfor; ?>
                        <?php
                            $totalPrice += $detail['totalPrice'];
                        endforeach; ?>
                    </div>

                    <!-- 2nd -->
                    <div class="checkout">
                        <h3 class="mb mt2">
                            <small>Step 2 of 3</small>
                            Billing & Contact
                        </h3>
                        <!-- form-data -->
                        <fieldset class="Billing_Contact">
                            <legend class="toggle statement">Confirmation and Billing Statements</legend>
                            <div class="content">
                                <div class="first">
                                    <label for="fname">First Name</label>
                                    <div class="input-icon">
                                        <input class="fields billing-field billing_participant_firstname" type="text" required id="fname" name="fname" onkeyup="billingField(this)" required>
                                        <span class="input-addon"></span>
                                    </div>
                                </div>
                                <div class="last">
                                    <label for="lname">Last Name</label>
                                    <div class="input-icon">
                                        <input class="fields billing-field billing_participant_lastname" type="text" required id="lname" name="lname" onkeyup="billingField(this)" required>
                                        <span class="input-addon"></span>
                                    </div>
                                </div>
                                <div class="mobile">
                                    <label for="phone">Mobile</label>
                                    <div class="input-icon">
                                        <input class="fields billing-field billing_participant_tel" type="tel" id="phone" name="phone" onkeyup="billingField(this)" required>
                                        <span class="input-addon"></span>
                                    </div>
                                </div>
                                <div class="email">
                                    <label for="email">Email</label>
                                    <div class="input-icon">
                                        <input class="fields billing-field billing_participant_email" type="email" required id="email" name="email" onkeyup="billingField(this)" required>
                                        <span class="input-addon"></span>
                                    </div>
                                </div>
                                <div class="country">
                                    <label for="country">Country</label>
                                    <?php
                                    $countries = array("af" => "Afghanistan", "ax" => "Aland Islands", "al" => "Albania", "dz" => "Algeria", "as" => "American Samoa", "ad" => "Andorra", "ao" => "Angola", "ai" => "Anguilla", "aq" => "Antarctica", "ag" => "Antigua and Barbuda", "ar" => "Argentina", "am" => "Armenia", "aw" => "Aruba", "au" => "Australia", "at" => "Austria", "az" => "Azerbaijan", "bs" => "Bahamas", "bh" => "Bahrain", "bd" => "Bangladesh", "bb" => "Barbados", "by" => "Belarus", "be" => "Belgium", "bz" => "Belize", "bj" => "Benin", "bm" => "Bermuda", "bt" => "Bhutan", "bo" => "Bolivia", "bq" => "Bonaire", "ba" => "Bosnia and Herzegovina", "bw" => "Botswana", "bv" => "Bouvet Island", "br" => "Brazil", "io" => "British Indian Ocean Territory", "bn" => "Brunei Darussalam", "bg" => "Bulgaria", "bf" => "Burkina Faso", "bi" => "Burundi", "kh" => "Cambodia", "cm" => "Cameroon", "ca" => "Canada", "cv" => "Cape Verde", "ky" => "Cayman Islands", "cf" => "Central African Republic", "td" => "Chad", "cl" => "Chile", "cn" => "China", "cx" => "Christmas Island", "cc" => "Cocos (Keeling) Islands", "co" => "Colombia", "km" => "Comoros", "cg" => "Congo", "cd" => "Congo, The Democratic Republic Of The", "ck" => "Cook Islands", "cr" => "Costa Rica", "ci" => "Cote D'ivoire", "hr" => "Croatia", "cu" => "Cuba", "cw" => "Curacao", "cy" => "Cyprus", "cz" => "Czech Republic", "dk" => "Denmark", "dj" => "Djibouti", "dm" => "Dominica", "do" => "Dominican Republic", "ec" => "Ecuador", "eg" => "Egypt", "sv" => "El Salvador", "gq" => "Equatorial Guinea", "er" => "Eritrea", "ee" => "Estonia", "et" => "Ethiopia", "fk" => "Falkland Islands", "fo" => "Faroe Islands", "fj" => "Fiji", "fi" => "Finland", "fr" => "France", "gf" => "French Guiana", "pf" => "French Polynesia", "tf" => "French Southern Territories", "ga" => "Gabon", "gm" => "Gambia", "ge" => "Georgia", "de" => "Germany", "gh" => "Ghana", "gi" => "Gibraltar", "gr" => "Greece", "gl" => "Greenland", "gd" => "Grenada", "gp" => "Guadeloupe", "gu" => "Guam", "gt" => "Guatemala", "gg" => "Guernsey", "gn" => "Guinea", "gw" => "Guinea-Bissau", "gy" => "Guyana", "ht" => "Haiti", "hm" => "Heard Island and Mcdonald Islands", "va" => "Holy See (Vatican City State)", "hn" => "Honduras", "hk" => "Hong Kong", "hu" => "Hungary", "is" => "Iceland", "in" => "India", "id" => "Indonesia", "ir" => "Iran", "iq" => "Iraq", "ie" => "Ireland", "im" => "Isle Of Man", "il" => "Israel", "it" => "Italy", "jm" => "Jamaica", "jp" => "Japan", "je" => "Jersey", "jo" => "Jordan", "kz" => "Kazakhstan", "ke" => "Kenya", "ki" => "Kiribati", "kp" => "Korea, Democratic People's Republic Of", "kr" => "Korea, Republic Of", "kw" => "Kuwait", "kg" => "Kyrgyzstan", "la" => "Lao People's Democratic Republic", "lv" => "Latvia", "lb" => "Lebanon", "ls" => "Lesotho", "lr" => "Liberia", "ly" => "Libya", "li" => "Liechtenstein", "lt" => "Lithuania", "lu" => "Luxembourg", "mo" => "Macao", "mk" => "Macedonia", "mg" => "Madagascar", "mw" => "Malawi", "my" => "Malaysia", "mv" => "Maldives", "ml" => "Mali", "mt" => "Malta", "mh" => "Marshall Islands", "mq" => "Martinique", "mr" => "Mauritania", "mu" => "Mauritius", "yt" => "Mayotte", "mx" => "Mexico", "fm" => "Micronesia", "md" => "Moldova", "mc" => "Monaco", "mn" => "Mongolia", "me" => "Montenegro", "ms" => "Montserrat", "ma" => "Morocco", "mz" => "Mozambique", "mm" => "Myanmar", "na" => "Namibia", "nr" => "Nauru", "np" => "Nepal", "nl" => "Netherlands", "nc" => "New Caledonia", "nz" => "New Zealand", "ni" => "Nicaragua", "ne" => "Niger", "ng" => "Nigeria", "nu" => "Niue", "nf" => "Norfolk Island", "mp" => "Northern Mariana Islands", "no" => "Norway", "om" => "Oman", "pk" => "Pakistan", "pw" => "Palau", "ps" => "Palestinian Territory", "pa" => "Panama", "pg" => "Papua New Guinea", "py" => "Paraguay", "pe" => "Peru", "ph" => "Philippines", "pn" => "Pitcairn", "pl" => "Poland", "pt" => "Portugal", "pr" => "Puerto Rico", "qa" => "Qatar", "re" => "Reunion", "ro" => "Romania", "ru" => "Russian Federation", "rw" => "Rwanda", "bl" => "Saint Barthelemy", "sh" => "Saint Helena", "kn" => "Saint Kitts and Nevis", "lc" => "Saint Lucia", "mf" => "Saint Martin", "pm" => "Saint Pierre and Miquelon", "vc" => "Saint Vincent and The Grenadines", "ws" => "Samoa", "sm" => "San Marino", "st" => "Sao Tome and Principe", "sa" => "Saudi Arabia", "sn" => "Senegal", "rs" => "Serbia", "sc" => "Seychelles", "sl" => "Sierra Leone", "sg" => "Singapore", "sx" => "Sint Maarten", "sk" => "Slovakia", "si" => "Slovenia", "sb" => "Solomon Islands", "so" => "Somalia", "za" => "South Africa", "gs" => "South Georgia and The South Sandwich Islands", "ss" => "South Sudan", "es" => "Spain", "lk" => "Sri Lanka", "sd" => "Sudan", "sr" => "Suriname", "sj" => "Svalbard and Jan Mayen", "sz" => "Swaziland", "se" => "Sweden", "ch" => "Switzerland", "sy" => "Syrian Arab Republic", "tw" => "Taiwan", "tj" => "Tajikistan", "tz" => "Tanzania", "th" => "Thailand", "tl" => "Timor-Leste", "tg" => "Togo", "tk" => "Tokelau", "to" => "Tonga", "tt" => "Trinidad and Tobago", "tn" => "Tunisia", "tr" => "Turkey", "tm" => "Turkmenistan", "tc" => "Turks and Caicos Islands", "tv" => "Tuvalu", "ug" => "Uganda", "ua" => "Ukraine", "ae" => "United Arab Emirates", "gb" => "United Kingdom", "us" => "United States", "um" => "United States Minor Outlying Islands", "uy" => "Uruguay", "uz" => "Uzbekistan", "vu" => "Vanuatu", "ve" => "Venezuela", "vn" => "Vietnam", "vg" => "Virgin Islands, British", "vi" => "Virgin Islands, U.S.", "wf" => "Wallis and Futuna", "eh" => "Western Sahara", "ye" => "Yemen", "zm" => "Zambia", "zw" => "Zimbabwe");
                                    ?>
                                    <select data-fieldtype="COUNTRY" name="country" class="country-select fields billing-field" onkeyup="billingField(this)">
                                        <option value="">Select...</option>
                                        <?php foreach ($countries as $code => $name) { ?>
                                            <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="text-area">
                                    <label for="text-area">Special requirements </label>
                                    <textarea class="fields" id="text-area" name="comments" rows="10" cols="50"></textarea>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <?php
                    if (isset($_ARRAY_SESSION[0]['voucherCode']) && !empty($_ARRAY_SESSION[0]['voucherCode']['codes'])) {
                        $totalPrice = $_ARRAY_SESSION[0]['codeData']['alltotalDue'];
                    } elseif (isset($_ARRAY_SESSION[0]['couponCode']) && !empty($_ARRAY_SESSION[0]['couponCode']['code'])) {
                        $totalPrice = $_ARRAY_SESSION[0]['codeData']['alltotalDue'];
                    }

                    ?>

                    <!-- 3rd -->
                    <div class="checkout payment_m">
                        <div class="title-flex">
                            <h3 class="mb">
                                <small>Step 3 of 3</small>
                                Payment
                            </h3>
                            <div class="emphasis pull-right nmt">
                                Secured with 2048-bit encryption
                            </div>
                        </div>
                        <!-- form-data -->
                        <fieldset class="Billing_Contact payment-third">
                            <div class="method_contents" id="method_contents_ID">
                                <?php if(!(get_option('cc_stripe_disable') == 'yes')): ?>
                                <div class="first">
                                    <input type="radio" id="stripe" name="radio" class="stripe_credit_card" onclick="stripeCreditCard(this)">
                                    <label for="paymentOption" class="mls stripe_credit_card" onclick="stripeCreditCard(this)">
                                        <div class="payment-content">
                                            Pay by Credit Card<br>
                                            <!-- <small class="tight">
                                            Credit Card Surcharge: +€0.00
                                        </small> -->
                                        </div>
                                        <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/stripe.svg'; ?>" alt="RezdyPay payment" width="100" height="30" class="rezdy-checkout">
                                    </label>
                                </div>
                                <?php endif; ?>
                                <div class="first">
                                    <input type="radio" id="PayPal" name="radio" class="PayPalPayment" onclick="PayPalPayment(this)">
                                    <label for="paymentOption" class="mls mls-2 PayPalPayment" onclick="PayPalPayment(this)">
                                        <div class="payment-content">
                                            Pay with PayPal
                                        </div>
                                        <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/paypal.png'; ?>" width="150" height="35" class="rezdy-checkout">
                                    </label>
                                </div>
                                <!-- ======== Airwallex ===== -->
                                <div class="first">
                                    <input type="radio" id="airwallex" name="radio" class="airwallex_payment_card" onclick="airwallexPaymentCard(this)">
                                    <label for="paymentOption" class="mls airwallex_payment_card" onclick="airwallexPaymentCard(this)">
                                        <div class="payment-content">
                                            Pay by Airwallex<br>
                                            
                                        </div>
                                        <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/airwallex_logo.svg'; ?>" alt="RezdyPay payment" width="100" height="30" class="rezdy-checkout">
                                    </label>
                                </div>
                                <!-- ======= End Airwallex ====== -->
                            </div>
                        </fieldset>
                        <div class="form-row stripe_card" style="display:none;">
                            <label for="card-element">
                                <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/icons8-lock-black.png'; ?>" alt=""> Secure and Encrypted Payment
                            </label>
                            <div class="card-detail-wrapper">
                                <div class="accepted-card-list">
                                    <div class="title">
                                        <h5 class="m-0">We Accept:</h5>
                                    </div>
                                    <div class="card-list">
                                        <ul class="m-0 p-0">
                                            <li>
                                                <span class="pay-card card-visa">
                                                    <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/visa.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-master">
                                                    <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/master.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-american">
                                                    <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/amex.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-diners">
                                                    <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/diners.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-discover">
                                                    <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/discover.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="input-icon">
                                    <div id="card-element">
                                        <!-- A Stripe Element will be inserted here. -->
                                    </div>
                                    <span class="input-addon"></span>
                                </div>
                            </div>

                            <!-- Used to display form errors. -->
                            <div id="card-errors" role="alert"></div>
                        </div>
                        <div class="form-row PayPal_mode" style="display:none;">
                            <p>You will be directed to a secure paypal payment website with click on PayPal button to complete your payment</p>
                            <div id="paypal-button-container"></div>
                        </div>
                        <div class="form-row airwallex_card" style="display:none;">
                        <label for="card-element">
                                <img src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/icons8-lock-black.png'; ?>" alt=""> Secure and Encrypted Payment
                            </label>
                            <div class="card-detail-wrapper">
                                <!-- <div class="accepted-card-list">
                                    <div class="title">
                                        <h5 class="m-0">We Accept:</h5>
                                    </div>
                                    <div class="card-list">
                                        <ul class="m-0 p-0">
                                            <li>
                                                <span class="pay-card card-visa">
                                                    <img src="< ?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/visa.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-master">
                                                    <img src="< ?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/master.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-american">
                                                    <img src="< ?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/amex.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-diners">
                                                    <img src="< ?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/diners.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                            <li>
                                                <span class="pay-card card-discover">
                                                    <img src="< ?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/discover.svg'; ?>" alt="">
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div> -->
                                <div class="input-icon">
                                     <!-- Add empty container for the card input element -->
                                    <div id="airwallex_element" class="airwallexElement">
                                        
                                    </div>

                                    <span class="input-addon"></span>
                                </div>
                            </div>

                            <!-- Used to display form errors. -->
                            <div id="airwallex-card-errors" role="alert"></div>
                            
                        </div>
                    </div>


                    <?php if ($totalPrice != 0) : ?>
                        <!-- button -->
                        <button type="button" class="btn btn-payment btn-submit pointer btn-invalid" id="create-booking" data-method="" onclick="create_booking()">
                            <span class="btn-payment-total">
                                <span class="bookingButtonText">Pay</span>&nbsp;<span class="update-on-order-total-change price" data-currency-base="EUR" data-original-amount="€<?= $totalPrice; ?>" data-price-value="<?= $totalPrice; ?>" title="Estimated conversion from 59">€<?= $totalPrice; ?></span>
                            </span>

                            <small class="invalid" id="paybutton_require_text">
                                Please enter all required fields
                            </small>
                        </button>
                    <?php else : ?>
                        <!-- button -->
                        <button type="button" class="btn btn-payment btn-submit pointer btn-invalid" id="create-booking" data-method="" onclick="create_booking()">
                            <span class="btn-payment-total">
                                <span class="bookingButtonText">Book Now</span>&nbsp;<span class="update-on-order-total-change price" data-currency-base="EUR" data-original-amount="€<?= $totalPrice; ?>" data-price-value="<?= $totalPrice; ?>" title="Estimated conversion from 59"></span>
                            </span>

                            <small class="invalid" id="paybutton_require_text">
                                Complete your Booking
                            </small>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- right side -->
                <div class="voucher-card">
                    <div class="card-inner">
                        <div class="card">
                            <div class="text-right">
                                <small class="price-label">EUR</small>
                            </div>
                            <?php

                            foreach ($response as $k => $detail) : ?>
                                <div class="main-item tour_<?= $k; ?>_schedule_time">
                                    <a class="item-delete" data-select_tour="tour_<?= $k; ?>_schedule_time" data-session="<?= $detail['schedule_time']; ?>" data-tour_url="<?= $detail['tour_url']; ?>" href="javascript:void(0)" onclick="itemDelete(this)">×</a>
                                    <div class="product-close">
                                        <div class="contents">
                                            <strong class="product-name">
                                                <?= $detail['name']; ?>
                                            </strong>
                                            <br>
                                            <small>
                                                <small>
                                                    Date:&nbsp;<?= $detail['sessionDate']; ?>
                                                </small>
                                            </small>
                                        </div>
                                        <div class="eur-price">
                                            <strong class="price">€<?= $detail['totalPrice']; ?></strong>
                                        </div>
                                    </div>
                                    <?php
                                    foreach ($detail['priceOptions'] as $i => $options) : ?>
                                        <?php if ($options['quantity'] > 0) : ?>
                                            <div class="sub-itm">
                                                <div class="price_left">
                                                    <input type="hidden" value="<?= $options['label']; ?>" name="priceOptions[<?= $k; ?>][<?= $i; ?>][optionLabel]" id="">

                                                    <input type="hidden" value="<?= $options['price']; ?>" name="priceOptions[<?= $k; ?>][<?= $i; ?>][price]" id="">

                                                    <input type="hidden" value="<?= $detail['name']; ?>" name="priceOptions[<?= $k; ?>][<?= $i; ?>][name]" id="">

                                                    <small><?= ($options['label'] == 'Quantity') ? 'Everyone' : $options['label']; ?> <br><small class="price">€<?= $options['price']; ?></small>
                                                    </small>
                                                </div>
                                                <div class="center-digit">
                                                    <input type="hidden" value="<?= $options['quantity']; ?>" name="priceOptions[<?= $k; ?>][<?= $i; ?>][value]" id="">
                                                    <small><?= $options['quantity']; ?></small>
                                                </div>
                                                <div class="price_right">
                                                    <small class="price">€<?= $options['sessionTotalPrice']; ?></small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php
                            endforeach;
                            ?>
                            <?php
                            if (isset($_ARRAY_SESSION[0]['voucherCode']) && !empty($_ARRAY_SESSION[0]['voucherCode']['codes'])) {
                                $Index = 0;
                                foreach ($_ARRAY_SESSION[0]['voucherCode']['codes'] as $codeIndex => $codeRowData) :
                            ?>
                                    <div class="main-item added_voucher" id="applied_codes_div" data-applied-code="<?= $codeIndex; ?>">
                                        <input type="hidden" name="applied_voucher_codes[<?= $Index; ?>][codeName]" value="<?= $codeIndex; ?>">
                                        <input type="hidden" name="applied_voucher_codes[<?= $Index; ?>][codePrice]" value="<?= $codeRowData['totalPaid']; ?>">
                                        <a class="item-delete" data-item-type="Voucher" data-code="<?= $codeIndex; ?>" href="javascript:void(0)" onclick="itemDelete(this)">×</a>
                                        <div class="product-close">
                                            <div class="contents">
                                                <strong class="code">
                                                    Voucher
                                                </strong>
                                                <br>
                                                <strong class="code-name">
                                                    <?= $codeIndex; ?>
                                                </strong>
                                                <?php
                                                if (isset($codeRowData['remaining']) && !empty($codeRowData['remaining'])) {
                                                ?>
                                                    <br>
                                                    <small class="block">
                                                        There will be <span class="price" title="Estimated conversion from 67.2">€<?= $codeRowData['remaining']; ?></span> remaining on your voucher
                                                    </small>
                                                <?php
                                                }
                                                ?>
                                            </div>
                                            <div class="eur-price">
                                                <strong class="code-price">€<?= $codeRowData['totalPaid']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                    $Index++;
                                endforeach;
                            }
                            ?>
                            <?php
                            if (isset($_ARRAY_SESSION[0]['couponCode']) && !empty($_ARRAY_SESSION[0]['couponCode']['code'])) {
                                foreach ($_ARRAY_SESSION[0]['couponCode']['code'] as $codeIndex => $codeRowData) :
                            ?>
                                    <div class="main-item added_coupon" id="applied_codes_div" data-applied-code="<?= $codeIndex; ?>">
                                        <input type="hidden" name="applied_coupon_code[codeName]" value="<?= $codeIndex; ?>">
                                        <input type="hidden" name="applied_coupon_code[codePrice]" value="<?= $codeRowData['totalPaid']; ?>">
                                        <a class="item-delete" data-item-type="PromoCode" data-code="<?= $codeIndex; ?>" href="javascript:void(0)" onclick="itemDelete(this)">×</a>
                                        <div class="product-close">
                                            <div class="contents">
                                                <strong class="code">
                                                    PromoCode
                                                </strong>
                                                <br>
                                                <strong class="code-name">
                                                    <?= $codeIndex; ?>
                                                </strong>
                                            </div>
                                            <div class="eur-price">
                                                <strong class="code-price">€<?= $codeRowData['totalPaid']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endforeach;
                            }
                            ?>
                            <div class="promo-code-box">
                                <div class="button toggle-btn">
                                    <small>
                                        <a class="btn_pointer">Add Promo code / Voucher</a>
                                    </small>
                                </div>
                                <div class="toggle-content">
                                    <div class="input-field">
                                        <input type="text" name="" id="p_v_code" class="fields" placeholder="Enter Voucher, Coupon or Promo Code">
                                    </div>
                                    <div class="apply-btn button">
                                        <input type="button" id="p_v_apply" value="Apply" onclick="pvapply(this)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cart-total">
                        <div class="subtotal">
                            <div class="subtotal-content">
                                <a href=""><strong>Subtotal</strong></a>
                                <a href="javascript:void(0)" class="info-subtotal">
                                    Includes taxes & fees&nbsp
                                    <img class="info-image" src="<?= trailingslashit(plugin_dir_url($this->appContext->getPluginFile())) . 'src/assets/images/icon-info-dark.svg'; ?>">
                                </a>
                            </div>
                            <div class="price">
                                <strong class="alltotal"><?= '€' . $totalPrice; ?></strong>
                            </div>
                        </div>
                        <div class="total">
                            <div class="total_eur">
                                <strong>Total (<span class="price-label">EUR</span>)</strong>
                            </div>
                            <div class="price">
                                <strong class="alltotal"><?= '€' . $totalPrice; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <input type="hidden" name="action" value="booking_checkout"> -->
    </form>
</div>

<?php get_footer(); ?>

<!-- intl-tel-input CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
<!-- intl-tel-input JS -->

<script src="<?php echo plugin_dir_url(__FILE__) . 'js/intlTelInput.min.js'; ?>"></script>
<script src="<?php echo plugin_dir_url(__FILE__) . 'js/utils.js'; ?>"></script>
<script src="https://js.stripe.com/v3/"></script>

<!-- ====== Airwallex ====== -->
<script src="https://checkout.airwallex.com/assets/elements.bundle.min.js"></script>
<!-- ===== End Airwallex ===== -->

<script src="<?php echo plugin_dir_url(__FILE__) . 'js/jquery-2.2.4.min.js'; ?>"></script>
<script>
    var cardFill = false;

    //Form to make Disabled
    var submit_button = document.getElementById('create-booking');
    if (submit_button) {
        submit_button.disabled = true;
    }


    //Payment Button text
    var paybutton_require_textElement = document.getElementById('paybutton_require_text');


    //Stripe card element
    var stripeKey = "<?php echo get_option('cc_stripe_pub_api_key'); ?>";
    var stripe = Stripe(stripeKey);
    var elements = stripe.elements();
    var style = {
        base: {
            iconColor: '#666EE8',
            color: '#31325F',
            lineHeight: '40px',
            fontWeight: 300,
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSize: '15px',
            '::placeholder': {
                color: '#CFD7E0',
            },
        },
    };
    var card = elements.create('card', {
        style: style,
        hidePostalCode: true,
    });
    var cardElement = document.querySelector('#card-element');
    if (cardElement) {
        card.mount('#card-element');
    }

    // ===== Airwallex Element ======
    var airwallexApiKey = "<?php echo get_option('cc_airwallex_secret_api_key'); ?>";
    var airwallexClientID = "<?php echo get_option('cc_airwallex_client_id'); ?>";

    Airwallex.init({
        env: 'demo', // Setup which Airwallex env('staging' | 'demo' | 'prod') to integrate with
        origin: window.location.origin, // Setup your event target to receive the browser events message
    });

    const airwallexCard = Airwallex.createElement('card');

    const airwallexElement = airwallexCard.mount("airwallex_element");
        
    // ======= End Airwallex Card Element =============
    

    document.addEventListener('DOMContentLoaded', function() {

        counter();

        var bodyElement = document.body;

        // Add your custom class to the body element
        bodyElement.classList.add('my-booking-checkout');

        // Add Progress bar Div under header
        var customContent = '<div class="progress-bar__wrapper"><div class="progress" role="progressbar" aria-label="Example 1px high" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px"><div class="progress-bar" style="width: 0%"></div></div></div>';
        jQuery(customContent).insertAfter(".loading_dot");


        phonenumber_flag();


        var checkout_quantity_inputs = document.querySelectorAll('.checkout-quantity-input');
        if (checkout_quantity_inputs.length > 0) {
            checkout_quantity_inputs.forEach(function(c_quantity_input) {
                if (c_quantity_input.value != '') {
                    var options_boxElement = c_quantity_input.closest('.options-box');
                    options_boxElement.classList.add('inputEnabled');
                }

            });
        }


        var viewportMetaTag = document.querySelector('meta[name="viewport"]');
        if (viewportMetaTag) {
            var currentContent = viewportMetaTag.getAttribute('content');
            var newContent = currentContent + ', maximum-scale=1.0';
            viewportMetaTag.setAttribute('content', newContent);
        }


        var pricespan_OnLoad = document.querySelector('.update-on-order-total-change');
        var priceValue_OnLoad = pricespan_OnLoad.getAttribute('data-price-value');
        if (priceValue_OnLoad <= 0) {
            if (document.querySelector('.payment_m')) {
                document.querySelector('.payment_m').style.display = 'none';
            }
            fieldsValidation();
        } else {
            if (document.querySelector('.payment_m')) {
                document.querySelector('.payment_m').style.display = 'block';
            }
        }

    });

    window.addEventListener("load", function() {
        var loadingElement = document.querySelector('.loading_dot');
        loadingElement.style.display = 'none'; // Hide the loader

        //Datalayer Function for begin_checkout
        const responsdataLayerCustom = sendDataToDataLayer();
    });

    function phonenumber_flag() {

        var input = document.querySelector("#phone");
        var iti = window.intlTelInput(input, {
            initialCountry: "auto",
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            geoIpLookup: function(callback) {
                // Use a geoip service to automatically select the user's country based on IP
                fetch('https://ipinfo.io/json')
                    .then(response => response.json())
                    .then(data => {

                        var countryCodeLower = data.country.toLowerCase();
                        country_Select(countryCodeLower);
                        var countryCode = data.country;
                        callback(countryCode);
                    })
                    .catch(error => {
                        console.log('Error fetching IP information:', error);
                    });
            }
        });

        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

    }

    function country_Select(countryCodeLower) {
        var selectElement = document.querySelector('.country-select');
        var optionToSelect = selectElement.querySelector('option[value="' + countryCodeLower + '"]');
        optionToSelect.selected = true;
    }

    function counter() {

        var formWrappers = document.getElementsByClassName('form-wrapper');
        for (var i = 0; i < formWrappers.length; i++) {
            var existingParagraph = formWrappers[i].querySelector('p.checkout-participants');
            if (existingParagraph) {
                var innerHTML = existingParagraph.innerHTML;
                existingParagraph.remove();
                var newParagraph = document.createElement('p');
                newParagraph.className = 'checkout-participants';
                newParagraph.style.setProperty('--process-number', "'" + (i + 1) + "'");
                newParagraph.innerHTML = innerHTML;
                formWrappers[i].appendChild(newParagraph);
            } else {
                var newParagraph = '<p class="checkout-participants" style="--process-number: \'' + (i + 1) + '\';">';
                while (formWrappers[i].childNodes.length > 0) {
                    var childNode = formWrappers[i].childNodes[0];
                    formWrappers[i].removeChild(childNode);
                    newParagraph += childNode.outerHTML || childNode.nodeValue;
                }
                newParagraph += '</p>';
                formWrappers[i].insertAdjacentHTML('afterbegin', newParagraph);
            }
        }

    }

    function stripeCreditCard(element) {
        var target = element;
        
        var mathodType = '';
        var radioButtonStripeJS = document.querySelectorAll('input[type="radio"][class="stripe_credit_card"]');
        radioButtonStripeJS.checked = true;
        var radioButtonPayPalJS = document.querySelectorAll('input[type="radio"][class="PayPalPayment"]');
        radioButtonPayPalJS.checked = false;

        // ====== airwallex =====
        var radioButtonAirwallexJS = document.querySelectorAll('input[type="radio"][class="airwallex_payment_card"]');
        radioButtonAirwallexJS.checked = false;
        // ======================

        var method_contents = jQuery('.method_contents');
        method_contents.addClass('checked');

        var radioButtonStripe = jQuery('input.stripe_credit_card');
        radioButtonStripe.addClass('selected').attr('checked', true);
        var radioButtonPayPal = jQuery('input.PayPalPayment');
        radioButtonPayPal.removeClass('selected').removeAttr('checked');
        // ==== airwallex =====
        var radioButtonAirwallex = jQuery('input.airwallex_payment_card');
        radioButtonAirwallex.removeClass('selected').removeAttr('checked');
        // ===================
        mathodType = 'stripe';
        submit_button.setAttribute('data-method', mathodType);
        
        var target_div_PayPal = document.getElementsByClassName('PayPal_mode');
        var target_div_stripe = document.getElementsByClassName('stripe_card');

        // ==== airwallex =====
        var target_div_airwallex = document.getElementsByClassName('airwallex_card');
        // ===================


        if (target_div_stripe[0].style.display == 'none' || target_div_stripe[0].style.display === '') {
            target_div_stripe[0].style.display = 'block';
            target_div_PayPal[0].style.display = 'none';
            // ==== airwallex =====
            target_div_airwallex[0].style.display = 'none';
            // ====================
            radioButtonStripe.addClass('selected').attr('checked', true);


            // Handle real-time validation errors
            card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                    submit_button.disabled = true;
                    jQuery('.btn-payment').addClass('btn-invalid');
                    paybutton_require_textElement.textContent = 'Please enter all required fields';
                    var status = false;
                    cardFill = false;
                    updateProgressBar(status);
                } else if (event.complete == true) {
                    displayError.textContent = '';
                    cardFill = true;
                    fieldsValidation();
                } else if (event.complete == false) {
                    displayError.textContent = 'Card is not completed Yet.';
                    submit_button.disabled = true;
                    jQuery('.btn-payment').addClass('btn-invalid');
                    paybutton_require_textElement.textContent = 'Please enter all required fields';
                    var status = false;
                    cardFill = false;
                    updateProgressBar(status);
                } else {
                    displayError.textContent = '';
                    jQuery('.btn-payment').removeClass('btn-invalid');
                    submit_button.disabled = false;
                    cardFill = true;
                }

            });

            if (cardFill == true) {
                fieldsValidation();

            } else {
                jQuery('.btn-payment').addClass('btn-invalid');
                paybutton_require_textElement.textContent = 'Please enter all required fields';
            }


        } else {

        }
    }

    function PayPalPayment(element) {
        var target = element;
        var mathodType = '';
        var radioButtonPayPalJS = document.querySelectorAll('input[type="radio"][class="PayPalPayment"]');
        radioButtonPayPalJS.checkedcardFill = true;
        var radioButtonStripeJS = document.querySelectorAll('input[type="radio"][class="stripe_credit_card"]');
        radioButtonStripeJS.checked = false;
        // ==== airwallex =====
        var radioButtonAirwallexJS = document.querySelectorAll('input[type="radio"][class="airwallex_payment_card"]');
        radioButtonAirwallexJS.checked = false;
        // ===================

        var method_contents = jQuery('.method_contents');
        method_contents.addClass('checked');


        var radioButtonPayPal = jQuery('input.PayPalPayment');
        radioButtonPayPal.addClass('selected').attr('checked', true);
        var radioButtonStripe = jQuery('input.stripe_credit_card');
        radioButtonStripe.removeClass('selected').removeAttr('checked');

        // ==== airwallex =====
        var radioButtonAirwallex = jQuery('input.airwallex_payment_card');
        radioButtonAirwallex.removeClass('selected').removeAttr('checked');
        // ===================

        mathodType = 'PayPal';
        submit_button.setAttribute('data-method', mathodType);
        

        var target_div_PayPal = document.getElementsByClassName('PayPal_mode');
        var target_div_stripe = document.getElementsByClassName('stripe_card');

        // ==== airwallex =====
        var target_div_airwallex = document.getElementsByClassName('airwallex_card');
        // ===================

        submit_button.disabled = false;
        jQuery('.btn-payment').removeClass('btn-invalid');

        if (target_div_PayPal[0].style.display == 'none' || target_div_PayPal[0].style.display === '') {
            target_div_PayPal[0].style.display = 'block';
            target_div_stripe[0].style.display = 'none';
            target_div_airwallex[0].style.display = 'none';
            cardFill = true;
        }
        fieldsValidation();
    }

    // ======== Airwallex ===== 

    function airwallexPaymentCard(element){
        
        var mathodType = '';

        var radioButtonAirwallexJS = document.querySelectorAll('input[type="radio"][class="airwallex_payment_card"]');
        radioButtonAirwallexJS.checked = true;

        var radioButtonStripeJS = document.querySelectorAll('input[type="radio"][class="stripe_credit_card"]');
        radioButtonStripeJS.checked = false;
        var radioButtonPayPalJS = document.querySelectorAll('input[type="radio"][class="PayPalPayment"]');
        radioButtonPayPalJS.checked = false;

        var method_contents = jQuery('.method_contents');
        method_contents.addClass('checked');

        var radioButtonAirwallex = jQuery('input.airwallex_payment_card');
        radioButtonAirwallex.addClass('selected').attr('checked', true);

        var radioButtonStripe = jQuery('input.stripe_credit_card');
        radioButtonStripe.removeClass('selected').removeAttr('checked');

        var radioButtonPayPal = jQuery('input.PayPalPayment');
        radioButtonPayPal.removeClass('selected').removeAttr('checked');

        mathodType = 'airwallex';
        submit_button.setAttribute('data-method', mathodType);
        
        var target_div_PayPal = document.getElementsByClassName('PayPal_mode');
        var target_div_stripe = document.getElementsByClassName('stripe_card');
        var target_div_airwallex = document.getElementsByClassName('airwallex_card');

        if (target_div_airwallex[0].style.display == 'none' || target_div_airwallex[0].style.display === '') {
            target_div_airwallex[0].style.display = 'block';
            target_div_stripe[0].style.display = 'none';
            target_div_PayPal[0].style.display = 'none';

            // Handle real-time validation errors
            var dom = airwallexCard.domElement;
            dom.addEventListener('onChange', (e) => {
                
                var displayError = document.getElementById('airwallex-card-errors');
                //console.log(e);
                // if (e.detail.error) {
                //     //console.log(e.detail);
                //     displayError.textContent = e.detail.error.message;
                //     submit_button.disabled = true;
                //     jQuery('.btn-payment').addClass('btn-invalid');
                //     paybutton_require_textElement.textContent = 'Please enter all required fields';
                //     jQuery('.airwallexElement').removeClass('AirwallexElement--complete');
                //     var status = false;
                //     cardFill = false;
                //     updateProgressBar(status);
                if (e.detail.complete === true) {   
                //} else if (e.detail.complete === true) {
                   
                    jQuery('.airwallexElement').addClass('AirwallexElement--complete');
                    displayError.textContent = '';
                    cardFill = true;
                    fieldsValidation();

                } else if (e.detail.complete === false) {
                    
                    jQuery('.airwallexElement').removeClass('AirwallexElement--complete');

                    displayError.textContent = 'Card is not completed Yet.';
                    submit_button.disabled = true;
                    jQuery('.btn-payment').addClass('btn-invalid');
                    paybutton_require_textElement.textContent = 'Please enter all required fields';
                    var status = false;
                    cardFill = false;
                    updateProgressBar(status);
        
                } else {
                   
                    jQuery('.airwallexElement').removeClass('AirwallexElement--complete');
                    displayError.textContent = e.detail.error.message;
                    submit_button.disabled = true;
                    jQuery('.btn-payment').addClass('btn-invalid');
                    paybutton_require_textElement.textContent = 'Please enter all required fields';
                    var status = false;
                    cardFill = false;
                    updateProgressBar(status);
                    
                }
            });

            if (cardFill == true) {
                fieldsValidation();

            } else {
                jQuery('.btn-payment').addClass('btn-invalid');
                paybutton_require_textElement.textContent = 'Please enter all required fields';
            }
        }
    }

    // ======= end airwallex =====

    function updateProgressBar(status) {

        if (status == false) {

            var form = document.querySelector('.booking-checkout');
            var requiredFields = form.querySelectorAll('[required]');
            var remainingEmptyFields = [];
            requiredFields.forEach(function(requiredField) {
                if (requiredField.value.trim() && requiredField.classList.contains('input-valid')) {
                    requiredField.classList.remove('error');
                } else {
                    remainingEmptyFields.push(requiredField.name);
                }
            });
            var value = requiredFields.length - remainingEmptyFields.length;
            var width = value * 15;
            var progress_bar = document.getElementsByClassName('progress-bar');
            progress_bar[0].style.width = width + '%';

        } else {
            var width = 100;
            var progress_bar = document.getElementsByClassName('progress-bar');
            progress_bar[0].style.width = width + '%';
        }
    }

    var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$/;

    function isValidEmail(email) {
        return emailPattern.test(email);
    }

    function fieldsValidation() {
        var form = document.querySelector('.booking-checkout');
        var requiredFields = form.querySelectorAll('[required]'); // Select all required fields

        var allFieldsFilled = true;
        requiredFields.forEach(function(field) {
            if (field.value.trim() && field.classList.contains('input-valid')) {
                field.classList.remove('error');
            } else {
                allFieldsFilled = false;
                field.classList.add('error');

            }

            field.addEventListener('keyup', function() {

                if (field.classList.contains('billing_participant_tel')) {

                    var placeholder = field.getAttribute('placeholder');
                    var trimmedPlaceholder = placeholder.replace(/\s/g, ''); // Remove all whitespace characters
                    var lengthWithoutSpaces = trimmedPlaceholder.length;
                    var inputValLen = field.value.length;

                    if (inputValLen >= 8 && inputValLen <= 11) {
                        field.classList.remove('error');
                        field.classList.add('input-valid');
                        field.closest('.input-icon').classList.add('input-valid');
                    } else if (inputValLen > (lengthWithoutSpaces) || inputValLen < (lengthWithoutSpaces)) {
                        field.classList.add('error');
                        field.classList.remove('input-valid');
                        field.closest('.input-icon').classList.remove('input-valid');
                    }
                } else if (field.classList.contains('billing_participant_email')) {

                    var email = field.value;
                    if (isValidEmail(email)) {
                        field.classList.remove('error');
                        field.classList.add('input-valid');
                        field.closest('.input-icon').classList.add('input-valid');

                    } else {
                        field.classList.add('error');
                        if (field.classList.contains('input-valid')) {
                            field.classList.remove('input-valid');
                        }
                        if (field.closest('.input-icon').classList.contains('input-valid')) {
                            field.closest('.input-icon').classList.remove('input-valid');
                        }
                    }
                } else {
                    if (field.value.trim()) {

                        field.classList.remove('error');
                        field.classList.add('input-valid');
                        field.closest('.input-icon').classList.add('input-valid');
                    } else {
                        field.classList.add('error');
                        if (field.classList.contains('input-valid')) {
                            field.classList.remove('input-valid');
                        }
                        if (field.closest('.input-icon').classList.contains('input-valid')) {
                            field.closest('.input-icon').classList.remove('input-valid');
                        }
                    }

                }


                var remainingEmptyFields = [];
                requiredFields.forEach(function(requiredField) {
                    if (requiredField.value.trim() && requiredField.classList.contains('input-valid')) {
                        requiredField.classList.remove('error');
                    } else {
                        remainingEmptyFields.push(requiredField.name);
                    }
                });

                if (remainingEmptyFields.length == 0) {
                    var pricespan = document.querySelector('.update-on-order-total-change');
                    var data__price__value = pricespan.getAttribute("data-price-value");
                    if (parseInt(data__price__value, 10) <= 0) {
                        var status = true;
                        updateProgressBar(status);
                        paybutton_require_textElement.textContent = 'Complete your booking';
                        jQuery('.btn-payment').removeClass('btn-invalid');
                        submit_button.disabled = false;
                    } else {
                        if (cardFill && cardFill == true) {
                            var status = true;
                            updateProgressBar(status);
                            paybutton_require_textElement.textContent = 'Complete your booking';
                            jQuery('.btn-payment').removeClass('btn-invalid');
                            submit_button.disabled = false;
                        } else {

                            var status = false;
                            updateProgressBar(status);
                            paybutton_require_textElement.textContent = 'Please enter all required fields';
                            submit_button.disabled = true;
                            jQuery('.btn-payment').addClass('btn-invalid');
                        }

                        var radiolables = form.querySelectorAll('.mls');
                        radiolables.forEach(function(radiolable) {
                            radiolable.style.borderBottomColor = '#62ba37';
                        });
                    }

                } else {
                    var status = false;
                    updateProgressBar(status);
                    paybutton_require_textElement.textContent = 'Please enter all required fields';
                    submit_button.disabled = true;
                    jQuery('.btn-payment').addClass('btn-invalid');

                    var radiolables = form.querySelectorAll('.mls');
                    radiolables.forEach(function(radiolable) {
                        radiolable.style.borderBottomColor = '#b43c3c';
                    });
                }

            });
        });
        if (!allFieldsFilled) {
            submit_button.disabled = true;
            jQuery('.btn-payment').addClass('btn-invalid');

            var radiolables = form.querySelectorAll('.mls');
            radiolables.forEach(function(radiolable) {
                radiolable.style.borderBottomColor = '#b43c3c';
            });

        } else {

            if (cardFill && cardFill == true) {
                var status = true;
                updateProgressBar(status);
                paybutton_require_textElement.textContent = 'Complete your booking';
                jQuery('.btn-payment').removeClass('btn-invalid');
                submit_button.disabled = false;
            } else {

                var status = false;
                updateProgressBar(status);
                paybutton_require_textElement.textContent = 'Please enter all required fields';
                submit_button.disabled = true;
                jQuery('.btn-payment').addClass('btn-invalid');
            }

            var radiolables = form.querySelectorAll('.mls');
            radiolables.forEach(function(radiolable) {
                radiolable.style.borderBottomColor = '#62ba37';
            });

        }
    }



    function alertDiv(alertClass, alertContent) {

        var existingAlerts = document.querySelectorAll('#alertMessages');
        if (existingAlerts.length > 0) {
            existingAlerts.forEach(function(alert) {
                alert.parentNode.removeChild(alert);
            });
        }
        // Create a new div element
        var alertDiv = document.createElement('div');

        // Add classes to the div
        alertDiv.className = 'alert alert-dismissible fade show ' + alertClass;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.setAttribute('id', 'alertMessages');

        // Create a text node for the message
        var messageTextNode = document.createTextNode(alertContent);

        // Create a button element
        var buttonElement = document.createElement('button');
        buttonElement.setAttribute('type', 'button');
        buttonElement.className = 'btn-close';
        buttonElement.setAttribute('data-bs-dismiss', 'alert');
        buttonElement.setAttribute('aria-label', 'Close');
        buttonElement.setAttribute('onclick', 'btn_close(this)');


        // Append elements to the div
        alertDiv.appendChild(messageTextNode);
        alertDiv.appendChild(buttonElement);



        var checkout_block = document.getElementById('booking_checkout_container');
        checkout_block.parentNode.insertBefore(alertDiv, checkout_block.nextSibling);

        setTimeout(function() {
            var existingAlerts = document.querySelectorAll('#alertMessages');
            if (existingAlerts.length > 0) {
                existingAlerts.forEach(function(alert) {
                    alert.classList.add('fadeOut');
                    alert.parentNode.removeChild(alert);
                });
            }
        }, 4000);

    }

    function btn_close(element) {
        element.closest('.alert').classList.add('fadeOut');
        element.closest('.alert').remove();
    }

    function editbooking(element) {
        var target = element;
        var data_target = target.getAttribute('data-target');
        var target_div = document.getElementsByClassName(data_target);
        if (target_div[0].style.display == 'none' || target_div[0].style.display === '') {
            target_div[0].style.display = 'block';
        } else {
            target_div[0].style.display = 'none';
        }
    }

    function checkout_quantity(element) {
        var target = element;

        if (target.value > 20) {

            var inputElement = target.nextElementSibling;
            inputElement.style.display = 'block';
            inputElement.focus();
            inputElement.closest('.options-box').classList.add('inputEnabled');
            if (target.name != '') {
                inputElement.setAttribute('name', target.name);
                target.setAttribute('name', '');
            }
            target.remove();


        } else {
            return;
        }
    }

    function updateInputValue(element) {
        var target = element;
        input__value = target.value.replace(/\D/g, '');
        if (input__value == parseInt(input__value, 10)) {
            if (target.value < 1) { // Ensure value is a number
                target.value = 1; // Reset value to 1 if less than 1 is typed
            } else {

                var closestFormFlex = target.closest('.form-flex');
                var fieldset = closestFormFlex.parentElement;
                var quantityRequiredMaxValue = fieldset.querySelector('.quantityRequiredMax').value;
                quantityRequiredMaxValue = parseInt(quantityRequiredMaxValue, 10);
                if (quantityRequiredMaxValue != NaN && quantityRequiredMaxValue < parseInt(input__value, 10)) {
                    var alertClass = 'alert-danger';
                    var alertContent = 'Max quantity you can add is ' + quantityRequiredMaxValue;
                    alertDiv(alertClass, alertContent);
                    target.value = quantityRequiredMaxValue;
                } else {
                    target.value = parseInt(input__value, 10);
                }

            }
        }

    }

    function create_booking() {

        var submit_button = document.getElementById('create-booking');
        var mathodType = document.getElementById('create-booking').getAttribute('data-method');
        var session_id = document.querySelector('.session_id').getAttribute('value');
        if (mathodType == 'stripe') {

            var method = 'CREDITCARD';
            submit_button.disabled = true;
            jQuery('.btn-payment').addClass('btn-invalid');

            // Create payment token
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    console.log(result.error);
                    // Inform the user if there was an error
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;

                    // Re-enable the submit button
                    submit_button.disabled = false;
                    jQuery('.btn-payment').removeClass('btn-invalid');

                } else {

                    // Token was created successfully, submit the form with token

                    paybutton_require_textElement.textContent = 'Processing..';

                    var tokenID = result.token.id;
                    var form = document.querySelector('.booking-checkout');
                    var pricespan = document.querySelector('.update-on-order-total-change');
                    var priceValue = pricespan.getAttribute('data-price-value');

                    var selected_flag_div = document.querySelector('.iti__selected-flag');
                    var title_text = selected_flag_div.getAttribute('title');
                    var selectedcountryCode = title_text.substring(title_text.lastIndexOf(":") + 2);

                    var data = {
                        action: 'booking_checkout',
                        priceValue: priceValue,
                        selectedcountryCode: selectedcountryCode,
                        method: method,
                        rezdy_session_id: session_id
                    };

                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripeToken');
                    hiddenInput.setAttribute('value', tokenID);
                    form.appendChild(hiddenInput);


                    var formData = new FormData(form);
                    for (var key in data) {
                        formData.append(key, data[key]);
                    }


                    var response = fetch(ajax_object.ajax_url, {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {

                            var baseURL = "<?php echo home_url(); ?>";
                            if (data.requestStatus == true) {

                                var transactionID = data.transactionID;

                                if (data.success_url != '') {
                                    var success_url = data.success_url;


                                    var transactionID = data.transactionID;
                                    window.location.href = success_url + '?transactionID=' + transactionID;


                                } else {
                                    var transactionID = data.transactionID;
                                    baseURL = baseURL + '/success?transactionID=' + transactionID;
                                    window.location.href = baseURL;
                                }

                            }
                            if (data.requestStatus == false) {
                                if (data.transactionID) {
                                    //payment success but Booking not
                                    if (data.cancel_url != '') {
                                        var cancel_url = data.cancel_url;
                                        var params = new URLSearchParams(cancel_url.search);
                                        var param = 'cancel';
                                        if (params.has(param)) {
                                            var transactionID = data.transactionID;
                                            baseURL = baseURL + '/cancel/' + transactionID;
                                            window.location.href = baseURL;
                                        } else {
                                            window.location.href = cancel_url;
                                        }
                                    } else {
                                        var transactionID = data.transactionID;
                                        baseURL = baseURL + '/cancel/' + transactionID;
                                        window.location.href = baseURL;
                                    }

                                } else {
                                    //payment and Booking both not
                                    if (data.cancel_url != '') {
                                        var cancel_url = data.cancel_url;
                                        var params = new URLSearchParams(cancel_url.search);
                                        var param = 'cancel';
                                        if (params.has(param)) {
                                            baseURL = baseURL + '/cancel/ ';
                                            window.location.href = baseURL;
                                        } else {
                                            window.location.href = cancel_url;
                                        }
                                    } else {
                                        baseURL = baseURL + '/cancel/ ';
                                        window.location.href = baseURL;
                                    }

                                }
                            }
                        })
                        .catch(function(error) {
                            console.log(error)
                        });

                }
            });
        } else if (mathodType == 'PayPal') {

            paybutton_require_textElement.textContent = 'Processing..';
            submit_button.disabled = true;
            jQuery('.btn-payment').addClass('btn-invalid');

            var method = 'PAYPAL';
            var form = document.querySelector('.booking-checkout');
            var pricespan = document.querySelector('.update-on-order-total-change');
            var priceValue = pricespan.getAttribute('data-price-value');

            var selected_flag_div = document.querySelector('.iti__selected-flag');
            var title_text = selected_flag_div.getAttribute('title');
            var selectedcountryCode = title_text.substring(title_text.lastIndexOf(":") + 2);

            var data = {
                action: 'booking_checkout',
                priceValue: priceValue,
                selectedcountryCode: selectedcountryCode,
                method: method,
                rezdy_session_id: session_id
            };
            var formData = new FormData(form);
            for (var key in data) {
                formData.append(key, data[key]);
            }
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.approveUrl) {
                        location.replace(data.approveUrl);
                    } else if (data.error) {
                        var alertClass = 'alert-danger';
                        var alertContent = data.error;
                        alertDiv(alertClass, alertContent);
                    } else {
                        var error = 'Something went wrong with payment details!!';
                        var alertClass = 'alert-danger';
                        var alertContent = error;
                        alertDiv(alertClass, alertContent);
                    }


                })
                .catch(function(error) {
                    console.log(error)
                });
            
        // ====== airwallex create booking ======

        } else if (mathodType == 'airwallex'){ 

            console.log(mathodType);

            var method = 'Airwallex';
     
            var data = {
                action: 'airwallex_auth_token',
                api_key: airwallexApiKey,
                client_id: airwallexClientID
            };

            var formData = new FormData();
            for (var key in data) {
                formData.append(key, data[key]);
            }
            
            var token = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData,
                })
                .then(function(token) {
                    return token.text();
                })
                .then(function(data) {
                    console.log(data);
                })
                .catch(function(error) {
                    console.log(error)
                });
                
                //JSON.stringify(token)

            //submit_button.disabled = true;
            //jQuery('.btn-payment').addClass('btn-invalid');

        //     Airwallex.confirmPaymentIntent({
        //         element: airwallexCard, // Provide Card element
        //         intent_id: '', // Payment Intent ID
        //         client_secret: airwallexClientID, // Client Secret
        //     }).then((response) => {
                
        //         //window.alert(JSON.stringify(response));
        //         console.log(JSON.stringify(response));
        //     })
        //     .catch((response) => {
        //         console.log('There was an error', response);
        //         //console.log(response.message);
        //   });

        
        // ====== end airwallex booking ======
        }else {

            paybutton_require_textElement.textContent = 'Processing..';
            submit_button.disabled = true;
            jQuery('.btn-payment').addClass('btn-invalid');

            var method = 'PROMO_CODE';
            var form = document.querySelector('.booking-checkout');
            var pricespan = document.querySelector('.update-on-order-total-change');
            var priceValue = pricespan.getAttribute('data-price-value');

            var selected_flag_div = document.querySelector('.iti__selected-flag');
            var title_text = selected_flag_div.getAttribute('title');
            var selectedcountryCode = title_text.substring(title_text.lastIndexOf(":") + 2);

            var data = {
                action: 'booking_checkout',
                priceValue: priceValue,
                selectedcountryCode: selectedcountryCode,
                method: method,
                rezdy_session_id: session_id
            };
            var formData = new FormData(form);
            for (var key in data) {
                formData.append(key, data[key]);
            }
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    var baseURL = "<?php echo home_url(); ?>";
                    if (data.requestStatus == true) {

                        var transactionID = data.transactionID;

                        if (data.success_url != '') {
                            var success_url = data.success_url;


                            var transactionID = data.transactionID;
                            window.location.href = success_url + '?transactionID=' + transactionID;


                        } else {
                            var transactionID = data.transactionID;
                            baseURL = baseURL + '/success?transactionID=' + transactionID;
                            window.location.href = baseURL;
                        }

                    }


                })
                .catch(function(error) {
                    console.log(error)
                });
        }
    }

    function sendDataToDataLayer() {

        var pricespan = document.querySelector('.update-on-order-total-change');
        var priceValue = pricespan.getAttribute('data-price-value');
        var data = {
            priceValue: priceValue,
        };

        var form = document.querySelector('.booking-checkout');
        var formData = new FormData(form);
        for (var key in data) {
            formData.append(key, data[key]);
        }

        var requestData = {};
        for (var [key, value] of formData.entries()) {
            requestData[key] = value;
        }

        let convertData = convertDataToJSON(requestData);
        let products = [];
        for (let i = 0; i < convertData.order.length; i++) {
            let order = convertData.order[i];
            let productCode = order.product_code;

            if (productCode && convertData.priceOptions[i]) {
                for (let j = 0; j < convertData.priceOptions[i].length; j++) {
                    let option = convertData.priceOptions[i][j];
                    let item = {
                        item_name: option.name,
                        item_id: productCode,
                        price: parseInt(option.price),
                        quantity: parseInt(option.value)
                    };
                    products.push(item);
                }
            }
        }

        let purchaseEventData = {};
        purchaseEventData = {
            event: "begin_checkout",
            ecommerce: {
                // Sum of (price * quantity) for all items.
                value: parseInt(priceValue, 10),
                tax: 0,
                shipping: 0,
                currency: "EUR",
                items: products,
            }
        };

        console.log(purchaseEventData);
        // Push the purchase event data to the dataLayer
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            ecommerce: null
        });
        window.dataLayer.push(purchaseEventData);
        return window.dataLayer;
    }

    function convertDataToJSON(data) {
        let result = {};

        // Iterate over the keys in the data object
        for (let key in data) {
            // Split the key based on '[' and ']'
            let parts = key.split(/\[|\]/).filter(Boolean);

            // Initialize the current object to the result object
            let currentObj = result;

            // Iterate over the parts of the key
            for (let i = 0; i < parts.length; i++) {
                let part = parts[i];

                // Check if it's the last part
                if (i === parts.length - 1) {
                    // Set the value in the current object
                    currentObj[part] = data[key];
                } else {
                    // Check if the next part is a number, initialize an array if necessary
                    let nextPart = parts[i + 1];
                    let isArray = /^\d+$/.test(nextPart);
                    if (!currentObj[part]) {
                        currentObj[part] = isArray ? [] : {};
                    }

                    // Move to the next object
                    currentObj = currentObj[part];
                }
            }
        }

        return result;
    }


    function pvapply(element) {
        var target = element;
        var p_v_code = document.getElementById('p_v_code');
        var pricespan = document.querySelector('.update-on-order-total-change');
        var priceValue = pricespan.getAttribute('data-price-value');
        var session_id = document.querySelector('.session_id').getAttribute('value');
        if (p_v_code.value.trim()) {
            if (priceValue > 0) {
                var remembercode = p_v_code.value.toUpperCase().replace(/\s/g, '');
                //Disable input and apply button on click
                p_v_code.disabled = true;
                target.disabled = true;
                target.value = 'Checking..';
                target.style.backgroundColor = 'var(--themeBase)';
                target.style.cursor = 'auto';

                var form = document.querySelector('.booking-checkout');
                var data = {
                    action: 'quote_booking_checkout',
                    p_v_code: remembercode,
                    priceValue: priceValue,
                    rezdy_session_id: session_id
                };
                var formData = new FormData(form);
                for (var key in data) {
                    formData.append(key, data[key]);
                }
                var response = fetch(ajax_object.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        //Enable input and apply button on click
                        p_v_code.disabled = false;
                        p_v_code.value = '';
                        target.disabled = false;
                        target.value = 'Apply';
                        target.style.backgroundColor = 'var(--themeBase)';
                        target.style.cursor = 'pointer';
                        jQuery('.promo-code-box .toggle-btn a').click();



                        if (data.requestStatus == true) {

                            var appliedCodesDivs = document.querySelectorAll('#applied_codes_div');
                            if (appliedCodesDivs) {
                                var countAppliedCodes = appliedCodesDivs.length;
                            } else {
                                var countAppliedCodes = 0;
                            }

                            if (data.codeType == 'coupon') {
                                var codeTypeName = 'PromoCode';
                                var codeTypeClasses = 'main-item added_coupon'
                                var inputfieldElement = '<input type="hidden" name="applied_coupon_code[codeName]" value="' + remembercode + '">';
                                var inputfieldElement2 = '<input type="hidden" name="applied_coupon_code[codePrice]" value="' + data.totalPaid + '">';
                            }
                            if (data.codeType == 'voucher') {
                                var codeTypeName = 'Voucher';
                                var codeTypeClasses = 'main-item added_voucher'
                                var inputfieldElement = '<input type="hidden" name="applied_voucher_codes[' + countAppliedCodes + '][codeName]" value="' + remembercode + '">';
                                var inputfieldElement2 = '<input type="hidden" name="applied_voucher_codes[' + countAppliedCodes + '][codePrice]" value="' + data.totalPaid + '">';
                            }

                            // Create a new div element
                            var newItem = document.createElement('div');
                            newItem.className = codeTypeClasses;
                            newItem.setAttribute('id', 'applied_codes_div');
                            newItem.setAttribute('data-applied-code', remembercode);

                            var remainingElement = '';
                            if (data.remaining) {
                                var remainingAmount = '€' + data.remaining;

                                remainingElement = '<br><small class="block">There will be <span class="price" title="Estimated conversion from 67.2">' + remainingAmount + '</span> remaining on your voucher</small>';

                            }

                            // Set inner HTML for the new div
                            newItem.innerHTML = `
                            ` + inputfieldElement + `
                            ` + inputfieldElement2 + `
                            <a class="item-delete" data-item-type="` + codeTypeName + `" data-code="` + remembercode + `" href="javascript:void(0)" onclick="itemDelete(this)">×</a>
                            <div class="product-close">
                                <div class="contents">
                                    <strong class="code">
                                        ` + codeTypeName + `
                                    </strong>
                                    <br>
                                    <strong class="code-name">
                                        ` + remembercode + `
                                    </strong>
                                    ` + remainingElement + `
                                </div>
                                <div class="eur-price">
                                    <strong class="code-price">€` + data.totalPaid + `</strong>
                                </div>
                            </div>
                        `;

                            // Get all existing main-item divs
                            var mainItems = document.querySelectorAll('.main-item');

                            // Get the last main-item div
                            var lastMainItem = mainItems[mainItems.length - 1];

                            // Append the new item after the last main-item div
                            lastMainItem.parentNode.insertBefore(newItem, lastMainItem.nextSibling);

                            var alertClass = 'alert-success';
                            var alertContent = codeTypeName + ' successfully applied!!';
                            alertDiv(alertClass, alertContent);

                            var strongElement = document.getElementsByClassName('alltotal');
                            var strongElementsArray = Array.from(strongElement);
                            strongElementsArray.forEach(function(element) {
                                element.textContent = '';
                                element.textContent = '€' + data.alltotalDue;
                            });

                            var pricespan = document.querySelector('.update-on-order-total-change');
                            pricespan.textContent = '€' + data.alltotalDue;
                            pricespan.setAttribute("data-original-amount", '€' + data.alltotalDue);
                            pricespan.setAttribute("data-price-value", data.alltotalDue);

                            if (data.amountToUpdate > 0) {
                                var added_coupon = document.querySelector('.added_coupon');

                                added_coupon.querySelector('[name="applied_coupon_code[codePrice]"]').value = data.amountToUpdate;
                                var product_close = added_coupon.querySelector('.product-close');
                                var eur_price = product_close.querySelector('.eur-price');
                                var code_price_span = product_close.querySelector('.code-price');
                                code_price_span.textContent = '€' + data.amountToUpdate;

                            }

                            //Scroll Up
                            jQuery("html, body").animate({
                                scrollTop: 0
                            }, 300);


                            var bookingButtonText = document.querySelector('.bookingButtonText');
                            if (data.alltotalDue < 0 || data.alltotalDue == 0) {

                                bookingButtonText.textContent = 'Book Now';
                                pricespan.textContent = '';

                                document.querySelector('.payment_m').style.display = 'none';
                                paybutton_require_textElement.textContent = 'Complete your booking';
                                fieldsValidation();
                                // jQuery('.btn-payment').removeClass('btn-invalid');
                                // submit_button.disabled = false;
                            }






                        } else if (data.requestStatus == false) {


                            var alertClass = 'alert-danger';
                            var alertContent = data.error;
                            alertDiv(alertClass, alertContent);

                            //Scroll Up
                            jQuery("html, body").animate({
                                scrollTop: 0
                            }, 300);

                        } else {
                            console.log(data.error);
                        }

                    })
                    .catch(function(error) {
                        console.log(error)
                    });
            } else {
                var alertClass = 'alert-danger';
                var alertContent = 'order value is empty OR equal to 0';
                alertDiv(alertClass, alertContent);
            }
        }
    }

    function updateItem(element) {
        var target = element;
        var schedule_time = target.getAttribute('data-schedule-time');
        var product_code = target.getAttribute('data-product-code');
        var session_date = target.getAttribute('data-session-date');

        var session_id = document.querySelector('.session_id').getAttribute('value');
        var fieldset = document.querySelector('.edit_booking_' + product_code + '_' + schedule_time);
        var elementsWithClass = fieldset.getElementsByClassName('checkout_quantity');
        var arrayOfElements = Array.from(elementsWithClass);

        var quantity_inputs = fieldset.getElementsByClassName('checkout-quantity-input');
        var arrayOfInputs = Array.from(quantity_inputs);



        var total_quantity = 0;
        var priceOptions = {};
        var data = {
            action: 'edit_booking',
            schedule_time: schedule_time,
            product_code: product_code,
            session_date: session_date,
            rezdy_session_id: session_id
        };
        var i = 0;
        var changedValue = 0;
        var changedElementName = '';
        if (arrayOfElements.length > 0) {
            arrayOfElements.forEach(function(selectElement) {

                if (!isNaN(parseInt(selectElement.value))) {

                    changedValue = selectElement.value;
                    changedElementName = selectElement.getAttribute('name');


                    total_quantity = total_quantity + parseInt(changedValue);
                    data[changedElementName] = parseInt(changedValue);

                }


            });
        }

        if (arrayOfInputs.length > 0) {
            arrayOfInputs.forEach(function(input_Element) {

                if (!isNaN(parseInt(input_Element.value))) {

                    changedValue = input_Element.value;
                    changedElementName = input_Element.getAttribute('name');

                    total_quantity = total_quantity + parseInt(changedValue);
                    data[changedElementName] = parseInt(changedValue);

                }

            });
        }


        target.disabled = true;
        target.value = 'Updating..';
        data['total_quantity'] = total_quantity;


        var sortedKeys = Object.keys(data).sort(function(a, b) {
            var regex = /\[(\d+)\]/;
            var numA = parseInt((regex.exec(a) || [])[1]);
            var numB = parseInt((regex.exec(b) || [])[1]);
            return numA - numB;
        });

        var sortedData = {};
        sortedKeys.forEach(function(key) {
            sortedData[key] = data[key];
        });

        var formData = new FormData();
        for (var key in sortedData) {
            formData.append(key, sortedData[key]);
        }

        var response = fetch(ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {

                if (data.response == true) {

                    //var urlToRedirect = window.location.href;
                    //window.location.href = urlToRedirect;
                    window.location.reload();
                } else {

                    var alertClass = 'alert-danger';
                    var alertContent = data.error;
                    alertDiv(alertClass, alertContent);
                    target.disabled = false;
                    target.value = 'Update';
                }

            })
            .catch(function(error) {
                return error;
            });



    }

    function isMobileDevice() {
        return window.innerWidth <= 768; // Adjust the threshold as per your requirement
    }

    function itemDelete(element) {
        var target = element;

        var loadingElement = document.querySelector('.loading_dot');
        if (isMobileDevice()) {
            jQuery('.loading_dot').show();
        } else {
            loadingElement.style.display = 'block';
        }

        var itemType = target.getAttribute('data-item-type');
        var dataCode = target.getAttribute('data-code');
        if (!itemType && !dataCode) {
            itemType = '';
            dataCode = '';
        }

        var sessionID = target.getAttribute('data-session');
        var session_id = document.querySelector('.session_id').getAttribute('value');
        var data = {
            action: 'delete_db_sessions',
            sessionID: sessionID,
            rezdy_session_id: session_id,
            itemType: itemType,
            dataCode: dataCode
        };
        var formData = new FormData();
        for (var key in data) {
            formData.append(key, data[key]);
        }
        var response = fetch(ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (document.querySelector('.payment_m')) {
                    document.querySelector('.payment_m').style.display = 'block';
                }
                if (data.codeRemoved == true && data.code) {
                    var elementToRemove = document.querySelector('[data-applied-code="' + data.code + '"]');
                    if (elementToRemove) {
                        elementToRemove.remove();
                        if (data.totalDuePrice) {

                            var strongElement = document.getElementsByClassName('alltotal');
                            var strongElementsArray = Array.from(strongElement);
                            strongElementsArray.forEach(function(element) {
                                element.textContent = '';
                                element.textContent = '€' + data.totalDuePrice;
                            });
                            var pricespan = document.querySelector('.update-on-order-total-change');
                            pricespan.textContent = '€' + data.totalDuePrice;
                            pricespan.setAttribute("data-original-amount", '€' + data.totalDuePrice);
                            pricespan.setAttribute("data-price-value", data.totalDuePrice);


                        }



                        var alertClass = 'alert-danger';
                        var alertContent = data.code_type + ' successfully removed!!';
                        alertDiv(alertClass, alertContent);


                        var bookingButtonText = document.querySelector('.bookingButtonText');
                        if (data.totalDuePrice > 0) {
                            bookingButtonText.textContent = 'Pay';
                            paybutton_require_textElement.textContent = 'Please enter all required fields';
                            document.getElementById('create-booking').disabled = true;
                            jQuery('.btn-payment').addClass('btn-invalid');
                        }

                        if (isMobileDevice()) {
                            jQuery('.loading_dot').hide();
                        } else {
                            loadingElement.style.display = 'none';
                        }


                    }


                } else if (data.response == true) {


                    var added_vouchers = document.querySelectorAll('.added_voucher');
                    if (added_vouchers) {
                        if (added_vouchers.length > 0) {
                            added_vouchers.forEach(function(added_voucher) {
                                added_voucher.parentNode.removeChild(added_voucher);
                            });
                        }
                    }

                    var added_coupon = document.querySelector('.added_coupon');
                    if (added_coupon) {
                        added_coupon.parentNode.removeChild(added_coupon);
                    }



                    var selectTourValue = target.getAttribute('data-select_tour');
                    var divsToRemove = document.querySelector('.' + selectTourValue);
                    var elementsWithClass = document.getElementsByClassName(selectTourValue);
                    var arrayOfElements = Array.from(elementsWithClass);
                    arrayOfElements.forEach(function(divToRemove) {
                        if (divToRemove) {
                            var elementscount = document.getElementsByClassName('main-item');
                            if (elementscount.length == 1) {
                                if (isMobileDevice()) {
                                    jQuery('.loading_dot').hide();
                                } else {
                                    loadingElement.style.display = 'none';
                                }
                                var baseURL = "<?php echo home_url(); ?>";
                                window.location.href = baseURL;
                            } else {
                                divToRemove.remove();
                                counter();
                            }

                        }
                    });
                    if (data.totalPrice) {

                        var strongElement = document.getElementsByClassName('alltotal');
                        var strongElementsArray = Array.from(strongElement);
                        strongElementsArray.forEach(function(element) {
                            element.textContent = '';
                            element.textContent = '€' + data.totalPrice;
                        });
                        var pricespan = document.querySelector('.update-on-order-total-change');
                        pricespan.textContent = '€' + data.totalPrice;
                        pricespan.setAttribute("data-original-amount", '€' + data.totalPrice);
                        pricespan.setAttribute("data-price-value", data.totalPrice);

                    }

                    if (isMobileDevice()) {
                        jQuery('.loading_dot').hide();
                    } else {
                        loadingElement.style.display = 'none';
                    }

                } else {

                    if (isMobileDevice()) {
                        jQuery('.loading_dot').hide();
                    } else {
                        loadingElement.style.display = 'none';
                    }
                }
            })
            .catch(function(error) {
                console.log(error)
            });
    }

    function makeAnotherBooking(element) {
        var target = element;
        var baseURL = "<?php echo home_url(); ?>";
        window.location.href = baseURL;
    }

    function participantFirstname(element) {
        var target = element;
        const fieldsets = document.getElementsByClassName('participant_firstname');
        const fieldsetsArray = Array.from(fieldsets);
        const clickedIndex = fieldsetsArray.indexOf(target);
        if (clickedIndex == 0) {
            document.querySelector('.billing_participant_firstname').value = target.value;
            document.querySelector('.billing_participant_firstname').classList.add('input-valid');
            document.querySelector('.billing_participant_firstname').closest('.input-icon').classList.add('input-valid');
            var status = false;
            updateProgressBar(status);
            if (target.value.length == 0) {
                document.querySelector('.billing_participant_firstname').classList.remove('input-valid');
                document.querySelector('.billing_participant_firstname').closest('.input-icon').classList.remove('input-valid');
            }
        }
    }

    function participantLastname(element) {
        var target = element;
        const fieldsets = document.getElementsByClassName('participant_lastname');
        const fieldsetsArray = Array.from(fieldsets);
        const clickedIndex = fieldsetsArray.indexOf(target);
        if (clickedIndex == 0) {
            document.querySelector('.billing_participant_lastname').value = target.value;
            document.querySelector('.billing_participant_lastname').classList.add('input-valid');
            document.querySelector('.billing_participant_lastname').closest('.input-icon').classList.add('input-valid');
            var status = false;
            updateProgressBar(status);
            if (target.value.length == 0) {
                document.querySelector('.billing_participant_lastname').classList.remove('input-valid');
                document.querySelector('.billing_participant_lastname').closest('.input-icon').classList.remove('input-valid');
            }
        }
    }

    function billingField(element) {
        var target = element;
        fieldsValidation();
    }


    jQuery(function() {
        jQuery('.promo-code-box .toggle-btn a').on('click', function() {
            jQuery(this).parents('.promo-code-box').find('.toggle-content').toggle('collapse');
        });
    });


    //Email validation in change
    const elementsNEW = document.getElementsByClassName('billing_participant_email');
    // Loop through each element and attach an event listener
    Array.from(elementsNEW).forEach(element => {
        element.addEventListener('change', function() {
            var email = this.value;
            if (isValidEmail(email)) {
                this.classList.remove('error');
                this.classList.add('input-valid');
                this.closest('.input-icon').classList.add('input-valid');

            } else {
                this.classList.add('error');
                if (this.classList.contains('input-valid')) {
                    this.classList.remove('input-valid');
                }
                if (this.closest('.input-icon').classList.contains('input-valid')) {
                    this.closest('.input-icon').classList.remove('input-valid');
                }
            }

        });
    });
</script>
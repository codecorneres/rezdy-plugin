<?php defined('ABSPATH') || exit; ?>

<?php
if (!session_id()) {
    session_start();
}
if(isset($response) && !empty($response)){
    // echo '<pre>';
    // print_r($response);
    // exit();
    global $wp_query;
    $checkout_id = $wp_query->query_vars['checkout_id'];
}else{
    wp_redirect( home_url());
    exit();
}
?>
<form action="" class="booking-checkout">

    <div class="checkout-details">
        <div class="checkout-block">
            <!-- 1st -->
            <div class="checkout">
                <h3 class="mb">
                    <small>Step 1 of 3</small>
                    Booking Details
                </h3>
                <?php $counter = 1; $totalPrice = 0;?>
                <?php foreach ($response as $k => $detail) : ?>
                    <div class="form-wrapper tour_<?= $k;?>_schedule_time">
                        
                            <strong><?= $detail['name']; ?></strong>
                            <input type="hidden" value="<?= $detail['productCode']; ?>" name="order[<?= $k; ?>][product_code]">
                            <small class="first">
                                <small class="two">
                                    <input type="hidden" value="<?= $detail['sessionDate']; ?>" name="order[<?= $k; ?>][sessionDate]">
                                    Date:&nbsp;<?= $detail['sessionDate']; ?>
                                </small>
                            </small>
                            <small class="third">
                                <a href="<?= $detail['tour_url']; ?>" class="pointer editbooking" data-target="edit-<?= $k; ?>">Edit Booking</a>
                            </small>
                        
                    </div>

                    <!-- Edit Booking toggle-->
                    <div class="order-edit-item edit-<?= $k; ?>" style="display:none;">
                        
                            <fieldset class="no-legend edit_booking_<?= $detail['productCode']; ?>_<?= $detail['schedule_time']; ?>">
                                <?php foreach ($detail['priceOptions'] as $key => $options) { ?>
                                    <div class="form-flex">
                                        <div class="label-box">
                                            <input type="hidden" name="ItemQuantity[<?= $detail['productCode']; ?>][<?= $key; ?>][priceOption][id]" id="" value="<?= $options['priceOptionID']; ?>">
                                            <h6><?php echo $options['label']; ?></h6>
                                            <p class="price" data-currency-base="" data-original-amount="<?php echo $options['price']; ?>"><?php echo '€' . $options['price']; ?></p>
                                        </div>
                                        <div class="options-box">
                                            <select name="ItemQuantity[<?= $options['priceOptionID']; ?>][<?= $key; ?>][quantity]" id="" class="quantity">
                                                <?php for ($i = 0; $i <= 20; $i++) : ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                                <option value="21">>20</option>
                                            </select>
                                            <input type="number" name="" id="" class="quantity-input" style="display: none;">
                                        </div>


                                    </div>
                                <?php  } ?>
                                
                                <div class="update-container">
                                    <input name="btn-update-item" id="btn-update-item" class="update-item btn fl mt-sm mb-sm" type="button" value="Update" data-product-code="<?= $detail['productCode']; ?>" data-schedule-time="<?= $detail['schedule_time']; ?>" data-session-date="<?= $detail['sessionDate']; ?>" style="background-color: rgba(255, 92, 0, 1);">		
                                </div>
                            </fieldset>
                        
                    </div>
                    
                    <!-- form-data -->
                    <?php for ($i = 0; $i < $detail['totalQuantity']; $i++) : ?>
                        <fieldset class="Billing_Contact participant_details tour_<?= $k;?>_schedule_time" style="--feild-number: '<?= $i + 1 ?>';">
                            <legend class="toggle">Participant</legend>
                            <div class="content">
                                <div class="first">
                                    <label for="fname">First Name</label>

                                    <input class="fields participant_firstname" type="text" id="fname" name="participant[<?= $k; ?>][<?= $i; ?>][first_name]">
                                </div>
                                <div class="last">
                                    <label for="lname">Last Name</label>
                                    <input class="fields participant_lastname" type="text" id="lname" name="participant[<?= $k; ?>][<?= $i; ?>][last_name]">
                                </div>
                            </div>
                        </fieldset>
                    <?php endfor; ?>
                <?php
                $totalPrice += $detail['totalPrice']; 
                endforeach;?>
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
                                <input class="fields billing_participant_firstname" type="text" required id="fname" name="fname">
                                <span class="input-addon"></span>
                            </div>
                        </div>
                        <div class="last">
                            <label for="lname">Last Name</label>
                            <div class="input-icon">
                                <input class="fields billing_participant_lastname" type="text" required id="lname" name="lname">
                                <span class="input-addon"></span>
                            </div>
                        </div>
                        <div class="mobile">
                            <label for="phone">Mobile</label>
                            <div class="input-icon">
                                <input class="fields" type="number" id="phone" name="phone">
                                <span class="input-addon"></span>
                            </div>
                        </div>
                        <div class="email">
                            <label for="email">Email</label>
                            <div class="input-icon">
                                <input class="fields" type="email" required id="email" name="email">
                                <span class="input-addon"></span>
                            </div>
                        </div>
                        <div class="country">
                            <label for="country">Country</label>
                            <select data-fieldtype="COUNTRY" name="country" class="country-select fields">
                                <option value="">Select...</option>
                                <option value="af">Afghanistan</option>
                                <option value="ax">Aland Islands</option>
                                <option value="al">Albania</option>
                                <option value="dz">Algeria</option>
                                <option value="as">American Samoa</option>
                                <option value="ad">Andorra</option>
                                <option value="ao">Angola</option>
                                <option value="ai">Anguilla</option>
                                <option value="aq">Antarctica</option>
                                <option value="ag">Antigua and Barbuda</option>
                                <option value="ar">Argentina</option>
                                <option value="am">Armenia</option>
                                <option value="aw">Aruba</option>
                                <option value="au">Australia</option>
                                <option value="at">Austria</option>
                                <option value="az">Azerbaijan</option>
                                <option value="bs">Bahamas</option>
                                <option value="bh">Bahrain</option>
                                <option value="bd">Bangladesh</option>
                                <option value="bb">Barbados</option>
                                <option value="by">Belarus</option>
                                <option value="be">Belgium</option>
                                <option value="bz">Belize</option>
                                <option value="bj">Benin</option>
                                <option value="bm">Bermuda</option>
                                <option value="bt">Bhutan</option>
                                <option value="bo">Bolivia</option>
                                <option value="bq">Bonaire</option>
                                <option value="ba">Bosnia and Herzegovina</option>
                                <option value="bw">Botswana</option>
                                <option value="bv">Bouvet Island</option>
                                <option value="br">Brazil</option>
                                <option value="io">British Indian Ocean Territory</option>
                                <option value="bn">Brunei Darussalam</option>
                                <option value="bg">Bulgaria</option>
                                <option value="bf">Burkina Faso</option>
                                <option value="bi">Burundi</option>
                                <option value="kh">Cambodia</option>
                                <option value="cm">Cameroon</option>
                                <option value="ca">Canada</option>
                                <option value="cv">Cape Verde</option>
                                <option value="ky">Cayman Islands</option>
                                <option value="cf">Central African Republic</option>
                                <option value="td">Chad</option>
                                <option value="cl">Chile</option>
                                <option value="cn">China</option>
                                <option value="cx">Christmas Island</option>
                                <option value="cc">Cocos (Keeling) Islands</option>
                                <option value="co">Colombia</option>
                                <option value="km">Comoros</option>
                                <option value="cg">Congo</option>
                                <option value="cd">Congo, The Democratic Republic Of The</option>
                                <option value="ck">Cook Islands</option>
                                <option value="cr">Costa Rica</option>
                                <option value="ci">Cote D'ivoire</option>
                                <option value="hr">Croatia</option>
                                <option value="cu">Cuba</option>
                                <option value="cw">Curacao</option>
                                <option value="cy">Cyprus</option>
                                <option value="cz">Czech Republic</option>
                                <option value="dk">Denmark</option>
                                <option value="dj">Djibouti</option>
                                <option value="dm">Dominica</option>
                                <option value="do">Dominican Republic</option>
                                <option value="ec">Ecuador</option>
                                <option value="eg">Egypt</option>
                                <option value="sv">El Salvador</option>
                                <option value="gq">Equatorial Guinea</option>
                                <option value="er">Eritrea</option>
                                <option value="ee">Estonia</option>
                                <option value="et">Ethiopia</option>
                                <option value="fk">Falkland Islands</option>
                                <option value="fo">Faroe Islands</option>
                                <option value="fj">Fiji</option>
                                <option value="fi">Finland</option>
                                <option value="fr">France</option>
                                <option value="gf">French Guiana</option>
                                <option value="pf">French Polynesia</option>
                                <option value="tf">French Southern Territories</option>
                                <option value="ga">Gabon</option>
                                <option value="gm">Gambia</option>
                                <option value="ge">Georgia</option>
                                <option value="de">Germany</option>
                                <option value="gh">Ghana</option>
                                <option value="gi">Gibraltar</option>
                                <option value="gr">Greece</option>
                                <option value="gl">Greenland</option>
                                <option value="gd">Grenada</option>
                                <option value="gp">Guadeloupe</option>
                                <option value="gu">Guam</option>
                                <option value="gt">Guatemala</option>
                                <option value="gg">Guernsey</option>
                                <option value="gn">Guinea</option>
                                <option value="gw">Guinea-Bissau</option>
                                <option value="gy">Guyana</option>
                                <option value="ht">Haiti</option>
                                <option value="hm">Heard Island and Mcdonald Islands</option>
                                <option value="va">Holy See (Vatican City State)</option>
                                <option value="hn">Honduras</option>
                                <option value="hk">Hong Kong</option>
                                <option value="hu">Hungary</option>
                                <option value="is">Iceland</option>
                                <option value="in">India</option>
                                <option value="id">Indonesia</option>
                                <option value="ir">Iran</option>
                                <option value="iq">Iraq</option>
                                <option value="ie">Ireland</option>
                                <option value="im">Isle Of Man</option>
                                <option value="il">Israel</option>
                                <option value="it">Italy</option>
                                <option value="jm">Jamaica</option>
                                <option value="jp">Japan</option>
                                <option value="je">Jersey</option>
                                <option value="jo">Jordan</option>
                                <option value="kz">Kazakhstan</option>
                                <option value="ke">Kenya</option>
                                <option value="ki">Kiribati</option>
                                <option value="kp">Korea, Democratic People's Republic Of</option>
                                <option value="kr">Korea, Republic Of</option>
                                <option value="kw">Kuwait</option>
                                <option value="kg">Kyrgyzstan</option>
                                <option value="la">Lao People's Democratic Republic</option>
                                <option value="lv">Latvia</option>
                                <option value="lb">Lebanon</option>
                                <option value="ls">Lesotho</option>
                                <option value="lr">Liberia</option>
                                <option value="ly">Libya</option>
                                <option value="li">Liechtenstein</option>
                                <option value="lt">Lithuania</option>
                                <option value="lu">Luxembourg</option>
                                <option value="mo">Macao</option>
                                <option value="mk">Macedonia</option>
                                <option value="mg">Madagascar</option>
                                <option value="mw">Malawi</option>
                                <option value="my">Malaysia</option>
                                <option value="mv">Maldives</option>
                                <option value="ml">Mali</option>
                                <option value="mt">Malta</option>
                                <option value="mh">Marshall Islands</option>
                                <option value="mq">Martinique</option>
                                <option value="mr">Mauritania</option>
                                <option value="mu">Mauritius</option>
                                <option value="yt">Mayotte</option>
                                <option value="mx">Mexico</option>
                                <option value="fm">Micronesia</option>
                                <option value="md">Moldova</option>
                                <option value="mc">Monaco</option>
                                <option value="mn">Mongolia</option>
                                <option value="me">Montenegro</option>
                                <option value="ms">Montserrat</option>
                                <option value="ma">Morocco</option>
                                <option value="mz">Mozambique</option>
                                <option value="mm">Myanmar</option>
                                <option value="na">Namibia</option>
                                <option value="nr">Nauru</option>
                                <option value="np">Nepal</option>
                                <option value="nl">Netherlands</option>
                                <option value="nc">New Caledonia</option>
                                <option value="nz">New Zealand</option>
                                <option value="ni">Nicaragua</option>
                                <option value="ne">Niger</option>
                                <option value="ng">Nigeria</option>
                                <option value="nu">Niue</option>
                                <option value="nf">Norfolk Island</option>
                                <option value="mp">Northern Mariana Islands</option>
                                <option value="no">Norway</option>
                                <option value="om">Oman</option>
                                <option value="pk">Pakistan</option>
                                <option value="pw">Palau</option>
                                <option value="ps">Palestinian Territory</option>
                                <option value="pa">Panama</option>
                                <option value="pg">Papua New Guinea</option>
                                <option value="py">Paraguay</option>
                                <option value="pe">Peru</option>
                                <option value="ph">Philippines</option>
                                <option value="pn">Pitcairn</option>
                                <option value="pl">Poland</option>
                                <option value="pt">Portugal</option>
                                <option value="pr">Puerto Rico</option>
                                <option value="qa">Qatar</option>
                                <option value="re">Reunion</option>
                                <option value="ro">Romania</option>
                                <option value="ru">Russian Federation</option>
                                <option value="rw">Rwanda</option>
                                <option value="bl">Saint Barthelemy</option>
                                <option value="sh">Saint Helena</option>
                                <option value="kn">Saint Kitts and Nevis</option>
                                <option value="lc">Saint Lucia</option>
                                <option value="mf">Saint Martin</option>
                                <option value="pm">Saint Pierre and Miquelon</option>
                                <option value="vc">Saint Vincent and The Grenadines</option>
                                <option value="ws">Samoa</option>
                                <option value="sm">San Marino</option>
                                <option value="st">Sao Tome and Principe</option>
                                <option value="sa">Saudi Arabia</option>
                                <option value="sn">Senegal</option>
                                <option value="rs">Serbia</option>
                                <option value="sc">Seychelles</option>
                                <option value="sl">Sierra Leone</option>
                                <option value="sg">Singapore</option>
                                <option value="sx">Sint Maarten</option>
                                <option value="sk">Slovakia</option>
                                <option value="si">Slovenia</option>
                                <option value="sb">Solomon Islands</option>
                                <option value="so">Somalia</option>
                                <option value="za">South Africa</option>
                                <option value="gs">South Georgia and The South Sandwich Islands</option>
                                <option value="ss">South Sudan</option>
                                <option value="es">Spain</option>
                                <option value="lk">Sri Lanka</option>
                                <option value="sd">Sudan</option>
                                <option value="sr">Suriname</option>
                                <option value="sj">Svalbard and Jan Mayen</option>
                                <option value="sz">Swaziland</option>
                                <option value="se">Sweden</option>
                                <option value="ch">Switzerland</option>
                                <option value="sy">Syrian Arab Republic</option>
                                <option value="tw">Taiwan</option>
                                <option value="tj">Tajikistan</option>
                                <option value="tz">Tanzania</option>
                                <option value="th">Thailand</option>
                                <option value="tl">Timor-Leste</option>
                                <option value="tg">Togo</option>
                                <option value="tk">Tokelau</option>
                                <option value="to">Tonga</option>
                                <option value="tt">Trinidad and Tobago</option>
                                <option value="tn">Tunisia</option>
                                <option value="tr">Turkey</option>
                                <option value="tm">Turkmenistan</option>
                                <option value="tc">Turks and Caicos Islands</option>
                                <option value="tv">Tuvalu</option>
                                <option value="ug">Uganda</option>
                                <option value="ua">Ukraine</option>
                                <option value="ae">United Arab Emirates</option>
                                <option value="gb">United Kingdom</option>
                                <option value="us">United States</option>
                                <option value="um">United States Minor Outlying Islands</option>
                                <option value="uy">Uruguay</option>
                                <option value="uz">Uzbekistan</option>
                                <option value="vu">Vanuatu</option>
                                <option value="ve">Venezuela</option>
                                <option value="vn">Vietnam</option>
                                <option value="vg">Virgin Islands, British</option>
                                <option value="vi">Virgin Islands, U.S.</option>
                                <option value="wf">Wallis and Futuna</option>
                                <option value="eh">Western Sahara</option>
                                <option value="ye">Yemen</option>
                                <option value="zm">Zambia</option>
                                <option value="zw">Zimbabwe</option>
                            </select>
                        </div>
                        <div class="text-area">
                            <label for="text-area">Special requirements </label>
                            <textarea class="fields" id="text-area" name="comments" rows="10" cols="50"></textarea>
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- 3rd -->
            <div class="checkout">
                <h3 class="mt mt2">
                    <div class="step-3">
                        <small>Step 3 of 3</small>
                        Payment
                    </div>
                    <small class="emphasis pull-right nmt">
                        Secured with 2048-bit encryption
                    </small>
                </h3>
                <!-- form-data -->
                <fieldset class="Billing_Contact payment-third">
                    <div class="contents">
                        <div class="first">
                            <input type="radio" id="radio" name="radio">
                              <label for="paymentOption" class="mls">
                                <div class="payment-content">
                                    Pay by Credit Card<br><br>
                                    <small class="tight">
                                        Credit Card Surcharge: +€0.00
                                    </small>
                                </div>
                                <img src="//static.rezdy-production.com/1890a432d193641d23fada2e43bcea7aeee684c71243/themes/rezdy-checkout/images/rezdypay-logo.png" alt="RezdyPay payment" width="100" height="30" class="rezdy-checkout">
                            </label>
                            <input type="radio" id="radio" name="radio">
                              <label for="paymentOption" class="mls mls-2">
                                <div class="payment-content">
                                    Pay with PayPal
                                </div>
                                <img src="images/paypal.png" width="150" height="35" class="rezdy-checkout">
                            </label>
                        </div>
                    </div>
                </fieldset>
            </div>
            <!-- button -->
            <button type="submit" class="btn btn-payment btn-submit pointer btn-invalid create-booking">
                <div class="btn-payment-total">
                    Pay <span class="update-on-order-total-change price" data-currency-base="EUR" data-original-amount="€<?= $totalPrice; ?>" data-price-value="<?= $totalPrice; ?>" title="Estimated conversion from 59">€<?= $totalPrice; ?></span> </div>

                <small class="invalid">
                    Please enter all required fields
                </small>
            </button>
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
                        <div class="main-item tour_<?= $k;?>_schedule_time" >
                            <a class="item-delete" data-select_tour="tour_<?= $k;?>_schedule_time" data-session="<?= $detail['schedule_time'];?>" data-tour_url="<?= $detail['tour_url'];?>" href="#">×</a>
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
                                <?php if ($options['quantity'] > 0) :?>
                                    <div class="sub-itm">
                                        <div class="price_left">
                                            <input type="hidden" value="<?= $options['label']; ?>" name="priceOptions[<?= $k; ?>][<?= $i; ?>][optionLabel]" id="">
                                            <small><?= $options['label']; ?> <br><small class="price">€<?= $options['price']; ?></small>
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
                                <?php endif;?>
                            <?php endforeach; ?>
                        </div>
                    <?php
                        endforeach; 
                    ?>
                    <div class="button">
                        <small>
                            <a class="btn_pointer">Add Promo code / Voucher</a>
                        </small>
                    </div>
                </div>
            </div>
            <div class="cart-total">
                <div class="subtotal">
                    <div class="subtotal-content">
                        <a href=""><strong>Subtotal</strong></a><br>
                        <a href="#">
                            Includes taxes & fees&nbsp
                            <img class="info-image" src="images/icon-info-dark.svg">
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
    <!-- <input type="hidden" name="action" value="booking_checkout"> -->
</form>



<script>
    document.addEventListener('DOMContentLoaded', function () {
        counter();
    });

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
            }
            else{
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


    var form = document.querySelector('.booking-checkout');
    form.addEventListener("submit", function(ev) {
        ev.preventDefault();
        var data = {
            action: 'booking_checkout'
        };
        var formData = new FormData(form);
        for (var key in data) {
            formData.append(key, data[key]);
        }
        var requestData = {};

        formData.forEach(function(value, key) {
            requestData[key] = value;
        });
        console.log(requestData)
        var response = fetch(ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.text();
            })
            .then(function(data) {
                console.log(data)
            })
            .catch(function(error) {
                console.log(error)
            });
    });


    document.body.addEventListener('click', async function (e) {
        e.preventDefault();
        var target = e.target;
        if(target.classList.contains('editbooking')){
            var data_target = target.getAttribute('data-target');
            var target_div = document.getElementsByClassName(data_target);
            if(target_div[0].style.display ==  'none' || target_div[0].style.display === ''){
                target_div[0].style.display =  'block';
            }else{
                target_div[0].style.display =  'none';
            }   
        }

        if(target.classList.contains('create-booking') || target.classList.contains('btn-payment-total')){
            var form = document.querySelector('.booking-checkout');
            var pricespan = document.querySelector('.update-on-order-total-change');
            var priceValue = pricespan.getAttribute('data-price-value');
            var data = {
                action: 'booking_checkout',
                priceValue: priceValue
            };
            var formData = new FormData(form);
            for (var key in data) {
                formData.append(key, data[key]);
            }
            
            var requestData = {};

            formData.forEach(function(value, key) {
                requestData[key] = value;
            });
            console.log(requestData);
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.text();
                })
                .then(function(data) {
                    console.log(data)
                })
                .catch(function(error) {
                    console.log(error)
                });
        }

        if(target.classList.contains('update-item')){
            
            var schedule_time = target.getAttribute('data-schedule-time');
            var product_code = target.getAttribute('data-product-code');  
            var session_date = target.getAttribute('data-session-date');
            var quantity_input = document.querySelector('.quantity-input');
            var checkout_id = "<?php echo $checkout_id; ?>";
            var fieldset = document.querySelector('.edit_booking_'+ product_code + '_' + schedule_time);
            var elementsWithClass = fieldset.getElementsByClassName('quantity');
            var arrayOfElements = Array.from(elementsWithClass);
            //console.log(arrayOfElements);
            var total_quantity = 0;
            var priceOptions = {};
            var data = {
                action: 'edit_booking',
                schedule_time: schedule_time,
                product_code: product_code,
                session_date: session_date,
                checkout_id: checkout_id,
                count: i
            };
            var i = 0;
            arrayOfElements.forEach(function(selectElement) {
                i = i + 1;
                total_quantity = total_quantity + parseInt(selectElement.value);
                    data[selectElement.name] = selectElement.value;     
            });
            data['total_quantity'] = total_quantity;
            quantity_input.value = i;

            var formData = new FormData();
            for (var key in data) {
                formData.append(key, data[key]);
            }
            formData.forEach(function(value, key) {
                formData[key] = value;
            });
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    //console.log('here we are in update function on checkout!!');
                    //console.log(data);
                    if(data.response == true){
                        //console.log(data.success);
                        location.reload();
                    }else{
                        console.log(data.error);
                    }

                })
                .catch(function(error) {
                    return error;
                }); 
        }
        if (target.classList.contains('item-delete')) {
            var sessionID = target.getAttribute('data-session');
            var checkout_id = "<?php echo $checkout_id; ?>";
            var data = {
                action      : 'delete_db_sessions',
                sessionID   : sessionID,
                checkout_id :checkout_id
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
                if(data.response == true){
                    var selectTourValue = target.getAttribute('data-select_tour');
                    var divsToRemove = document.querySelector('.' + selectTourValue);
                    var elementsWithClass = document.getElementsByClassName(selectTourValue);
                    var arrayOfElements = Array.from(elementsWithClass);
                    console.log(arrayOfElements);
                    arrayOfElements.forEach(function(divToRemove) {
                        if (divToRemove) {
                            divToRemove.remove();
                            var elementscount = document.getElementsByClassName('main-item');
                            if(elementscount.length == 0){
                                var tour_url = target.getAttribute('data-tour_url');
                                window.location.href = tour_url;
                            }else{
                                counter();
                            }
                            
                        }
                    });
                    if(data.totalPrice){
                        var strongElement = document.getElementsByClassName('alltotal');
                        var strongElementsArray = Array.from(strongElement);
                        strongElementsArray.forEach(function(element) {
                            element.textContent = '';
                            element.textContent = '€' + data.totalPrice;
                        });
                    }
                    
                }else{
                    console.log('something wrong!!');
                }
            })
            .catch(function(error) {
                console.log(error)
            });  
        }

    });

    document.body.addEventListener('change', async function (e) {
        e.preventDefault();
        var target = e.target;
        if (target.classList.contains('participant_firstname')) {
            const fieldsets = document.getElementsByClassName('participant_firstname');
            const fieldsetsArray = Array.from(fieldsets);
            const clickedIndex = fieldsetsArray.indexOf(target);
            if(clickedIndex == 0){
                document.querySelector('.billing_participant_firstname').value = target.value;
            }
        }
        if (target.classList.contains('participant_lastname')) {
            const fieldsets = document.getElementsByClassName('participant_lastname');
            const fieldsetsArray = Array.from(fieldsets);
            const clickedIndex = fieldsetsArray.indexOf(target);
            if(clickedIndex == 0){
                document.querySelector('.billing_participant_lastname').value = target.value;
            }
        }
    });
</script>


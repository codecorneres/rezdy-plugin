<?php defined('ABSPATH') || exit; ?>

<div class="booking-sidebar-widget-box in calendar-widget <?php echo (!empty(get_option('cc_picked_color'))) ? get_option('cc_picked_color') : 'theme-cdt'; ?>">
    <div class="booking-inner availability-container">
        <div class="booking-form-list">
            <?php
            $cookie_name = "CUSTOMSESSIONID";
            if (!isset($_COOKIE[$cookie_name])) {
                session_start();
                $session_id = session_id();
                $cookie_value = $session_id;
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // 86400 = 1 day
            } else {
                $session_id = $_COOKIE[$cookie_name];
            }
            ?>
            <input type="hidden" value="<?php echo $session_id; ?>">
            <form action="<?= esc_url(site_url('/checkout/' . $session_id)); ?>" method="post" id="session-form" onsubmit="return validateForm()">
                <div class="booking-group">
                    <div class="booking-single">
                        <div class="title">
                            <h5>Enter Number of Participants <span class="required">*</span></h5>
                        </div>
                        <input type="hidden" name="OrderItem[preferredDate]" value="" id="selectedDate">
                        <input type="hidden" name="OrderItem[productCode]" value="<?= $product->product->productCode; ?>" id="productCode">
                        <input type="hidden" name="OrderItem[quantityRequiredMax]" class="quantityRequiredMax" value="<?= ($quantityRequiredMax) ? $quantityRequiredMax : ''; ?>" id="quantityRequiredMax">
                        <div class="parent-form-flex">
                            <?php foreach ($priceOptions as $key => $value) { ?>
                                <div class="form-flex shadow-box">
                                    <div class="label-box">
                                        <input type="hidden" class="priceOption_id" name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][priceOption][id]" id="" value="<?= $value->id; ?>">
                                        <input type="hidden" class="priceOption_label" name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][priceOption][label]" id="" value="<?= $value->label; ?>">
                                        <h6 class="priceOptionlabel"><?php echo ($value->label == 'Quantity') ? 'Everyone' : $value->label; ?></h6>
                                        <p class="price option_price" data-currency-base="<?php echo $product->product->currency; ?>" data-original-amount="<?php echo $value->price; ?>" data-value="<?= $value->id; ?>" data-label="<?php echo $value->label; ?>"><?php echo '€' . $value->price . '.00'; ?></p>
                                    </div>
                                    <div class="options-box">
                                        <select name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][quantity]" id="" class="quantity">
                                            <?php if ($quantityRequiredMax && $quantityRequiredMax <= 20) : ?>
                                                <?php for ($i = 0; $i <= $quantityRequiredMax; $i++) : ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            <?php else : ?>
                                                <?php for ($i = 0; $i <= 20; $i++) : ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                                <option value="21" data-value="21">>20</option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="number" name="" id="" class="quantity-input" style="display: none;">
                                    </div>


                                </div>
                            <?php  } ?>
                        </div>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Date <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex ">
                                <div class="calendar datepicker-container position-relative">
                                    <div id="datepicker" class="availabilitypicker">

                                    </div>

                                    <div class="rezdy-overlay-loader" style="display: none;">
                                        <div class="loading-text">Loading...</div>
                                    </div>

                                </div>
                                <div class="status-codes">
                                    <div class="status-single">
                                        <div class="status-bar today"></div>
                                        <div class="text">- TODAY’S DATE</div>
                                    </div>
                                    <div class="status-single">
                                        <div class="status-bar available"></div>
                                        <div class="text">- AVAILABLE DATE</div>
                                    </div>
                                    <div class="status-single">
                                        <div class="status-bar selected"></div>
                                        <div class="text">- SELECTED DATE</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-single" id="selectTimeDiv">
                            <div class="title">
                                <h5>Select Time <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex">
                                <input type="hidden" class="schedule_timeInput" name="schedule_time" value="">
                                <div class="radio-group-list" id="availability">

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="price-box price-summary">
                        <h5>Price(EUR)</h5>
                        <h4 class="total-price-value">€0</h4>
                    </div>
                    <div class="btn-submit-box">
                        <input type="hidden" name="tour_url" id="tour_url" value="">
                        <button type="submit" class="btn-submit form-submit disabled" disabled>Book Now</button>
                    </div>

                    <!-- <div class="booking-feature-list">
                        <div class="list-single">
                            <div class="inner shadow-box">
                                <span class="icon">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.89467 14C5.89271 14 5.89043 14 5.88847 14C5.8075 13.9982 5.73111 13.9595 5.67626 13.8932L0.0783668 7.09603C-0.0176194 6.97936 -0.0264345 6.80364 0.0574718 6.67567C0.141378 6.54807 0.294172 6.50432 0.422807 6.5714L5.54632 9.25031C5.58941 9.27291 5.64034 9.26197 5.67332 9.22369L13.4887 0.102372C13.5945 -0.0212193 13.7675 -0.0347086 13.8876 0.0717475C14.0078 0.178204 14.0355 0.369241 13.9516 0.51252L6.17741 13.8115C6.16631 13.8308 6.15325 13.8483 6.13888 13.8647L6.1046 13.903C6.04877 13.965 5.97303 14 5.89467 14Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="text">Free 24hr cancellations</span>
                            </div>
                        </div>
                        <div class="list-single">
                            <div class="inner shadow-box">
                                <span class="icon">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.89467 14C5.89271 14 5.89043 14 5.88847 14C5.8075 13.9982 5.73111 13.9595 5.67626 13.8932L0.0783668 7.09603C-0.0176194 6.97936 -0.0264345 6.80364 0.0574718 6.67567C0.141378 6.54807 0.294172 6.50432 0.422807 6.5714L5.54632 9.25031C5.58941 9.27291 5.64034 9.26197 5.67332 9.22369L13.4887 0.102372C13.5945 -0.0212193 13.7675 -0.0347086 13.8876 0.0717475C14.0078 0.178204 14.0355 0.369241 13.9516 0.51252L6.17741 13.8115C6.16631 13.8308 6.15325 13.8483 6.13888 13.8647L6.1046 13.903C6.04877 13.965 5.97303 14 5.89467 14Z" fill="currentColor" />
                                    </svg>
                                </span>
                                <span class="text">prices are all - inclusive</span>
                            </div>
                        </div>
                    </div> -->

                    <!-- <div class="form-note">
                            <p><b>Please note:</b> After your purchase is confirmed we will email you a confirmation.</p>
                        </div> -->
            </form>
        </div>
    </div>
</div>
</div>

<style>
    .ui-datepicker-unselectable {
        cursor: default !important;
    }
</style>
<?php
$dates = [];
foreach ($availabilities as $key => $value) {
    $dates[] = date('Y-m-d', strtotime($value->startTimeLocal));
}

$dates = array_unique($dates);

?>

<script src="<?php echo plugin_dir_url(__FILE__) . 'js/jquery-2.2.4.min.js'; ?>"></script>

<script>
    $(function() {

        var checkedRadioButton = '';

        //Submit button
        var buttonSubmit = document.querySelector(".form-submit"); // Replace with the ID of your submit button
        buttonSubmit.classList.add('disabled');
        buttonSubmit.setAttribute('disabled', true);


        var nextMonthClicked = false;


        var enabledDates = <?php echo json_encode($dates); ?>;
        var datesArray = Object.keys(enabledDates).map(key => enabledDates[key]);

        function enableDates(date) {
            var formattedDate = $.datepicker.formatDate("yy-mm-dd", date);
            return datesArray.indexOf(formattedDate) !== -1;
        }

        var loading = document.querySelector('.rezdy-overlay-loader');
        var buttonSubmit = document.querySelector('.form-submit');

        function isMobileDevice() {
            return window.innerWidth <= 768; // Adjust the threshold as per your requirement
        }

        function showLoading() {
            if (loading) {
                if (isMobileDevice()) {
                    jQuery('.rezdy-overlay-loader').css('display', 'block');
                    jQuery('.form-submit').addClass('disabled');
                } else {
                    loading.style.display = 'block';
                    buttonSubmit.disabled = true;
                }

            }
        }

        function hideLoading() {
            if (loading) {
                if (isMobileDevice()) {
                    jQuery('.rezdy-overlay-loader').css('display', 'none');
                    jQuery('.form-submit').removeClass('disabled');
                } else {
                    loading.style.display = 'none';
                }

            }
        }

        var startDate;
        var endDate;

        function formatDate(date) {
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2); // Adding 1 because months are 0-indexed
            var day = ('0' + date.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        }

        function updateStartEndDate(year, month) {
            startDate = new Date(year, month - 1, 1); // month is 0-indexed
            endDate = new Date(year, month, 0); // The last day of the month
        }

        // Initial start and end date for the next month when the page loads
        var currentDate = new Date();
        var nextMonth = currentDate.getMonth() + 2; // Adding 2 because month is 0-indexed
        var nextYear = currentDate.getFullYear();
        if (nextMonth > 12) {
            nextMonth -= 12;
            nextYear += 1;
        }
        updateStartEndDate(nextYear, nextMonth);


        // Determine the first available date
        function firstAvailableDate(datesArray) {
            var firstAvailableDate = null;
            for (var i = 0; i < datesArray.length; i++) {
                var parts = datesArray[i].split('-');
                //var parts = datesArray[i];
                var date = new Date(parts[0], parts[1] - 1, parts[2]);
                if (!isNaN(date.getTime())) {
                    firstAvailableDate = date;
                    break;
                }
            }
            return firstAvailableDate;
        }

        $(".availabilitypicker").datepicker({
            prevText: "←",
            nextText: "→",
            minDate: 0,
            defaultDate: firstAvailableDate(datesArray), // Set the default date
            dateFormat: "yy-mm-dd",
            beforeShowDay: function(date) {
                var currentDate = new Date();

                currentDate.setHours(0, 0, 0, 0); // Reset hours, minutes, seconds, and milliseconds to compare dates
                date.setHours(0, 0, 0, 0);

                if (date.getTime() === currentDate.getTime()) {
                    // This is the current date, don't mark it as 'old-date'
                    return [true, ''];
                }

                if (date < currentDate) {
                    return [false, 'old-date'];
                }

                if (enableDates(date)) {
                    return [true, "enabled"];
                } else {
                    return [false, "disabled"];
                }
            },
            onSelect: function() {
                nextMonthClicked = false;
                selectedDate = $.datepicker.formatDate("yy-m-d", $(this).datepicker("getDate"));
                document.querySelector('#selectedDate').value = selectedDate;

                var radioButtons = document.querySelectorAll('.availableRadiobutton');
                if (radioButtons.length > 0) {

                    radioButtons.forEach(function(radioButton) {
                        if (radioButton.checked) {
                            checkedRadioButton = radioButton.getAttribute('id');
                        }
                    });
                }

                fetching_availabilities(checkedRadioButton);
            },
            onChangeMonthYear: function(year, month, inst) {
                nextMonthClicked = true;
                updateStartEndDate(year, month);

                selectedDate = formatDate(startDate);

                //check if there is any available radio buttons
                var existingRadioGroups = document.querySelectorAll('.radio-group');

                // If an existing 'radio-group' element is found, remove it
                existingRadioGroups.forEach(function(radioGroup) {
                    radioGroup.remove();
                });
                document.querySelector('.total-price-value').textContent = '€0';

                fetching_availabilities(checkedRadioButton);
            }
        });


        function fetching_availabilities(checkedRadioButton) {
            
            if (isMobileDevice()) {
                jQuery('.form-submit').addClass('disabled');
                jQuery('.form-submit').prop('disabled', true);
            } else {
                buttonSubmit.classList.add('disabled');
                buttonSubmit.setAttribute('disabled', true);
            }
            var form = document.querySelector('#session-form');

            var data = {
                action: 'fetching_availabilities',
                firstDate: selectedDate,
                nextMonthClicked: nextMonthClicked
            };
            var formData = new FormData(form);

            for (var key in data) {
                formData.append(key, data[key]);
            }

            showLoading();
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    document.getElementById('availability').innerHTML = '';
                    var selectedOption = false;
                    var selectTimeDiv = document.getElementById('selectTimeDiv');
                    document.getElementById('selectTimeDiv').style.display = 'block';
                    if (nextMonthClicked && data.availability) {
                        var availabilityArray = data.availability;
                        datesArray.length = 0;
                        datesArray = $.map(availabilityArray, function(value, key) {
                            return [key];
                        });
                        if (datesArray.length > 0) {
                            var availabilityArray = data.availability;
                            datesArray.length = 0;
                            datesArray = $.map(availabilityArray, function(value, key) {
                                return [key];
                            });
                            var startDate = firstAvailableDate(datesArray);

                            $(".availabilitypicker").datepicker('setDate', startDate);
                            var originalDateObject = new Date(startDate);
                            var formattedSelectedDate = $.datepicker.formatDate("mm/dd/yy", originalDateObject);
                            document.querySelector('#selectedDate').value = formattedSelectedDate;

                        }
                    }
                    if (data.sessionTimeLabel) {
                        var radio_i = 1;
                        for (const key in data.sessionTimeLabel) {
                            if (Object.hasOwnProperty.call(data.sessionTimeLabel, key)) {
                                const value = data.sessionTimeLabel[key];
                                var totalAvailable = value.split(' ')[2] + ' ' + value.split(' ')[3];
                                var time = value.split(' ')[0];
                                var [hour, minute] = time.split(':').map(Number);
                                minute = (minute < 10 ? "0" + minute : minute);
                                var period = hour < 12 ? 'AM' : 'PM';
                                time = hour + ":" + minute + " " + period;
                                var price = data.totalPrice[key];
                                var activeSession = data.activeSession[key];
                                // Create Radio elements
                                var radioGroup = document.createElement('div');
                                radioGroup.classList.add('radio-group');
                                var inputRadio = document.createElement('input');
                                inputRadio.setAttribute('type', 'radio');
                                inputRadio.setAttribute('name', 'radio');
                                inputRadio.setAttribute('id', 'radio_' + radio_i);
                                inputRadio.setAttribute("data-price", price);
                                inputRadio.setAttribute("data-disabled", activeSession);
                                inputRadio.setAttribute("data-session_time", `${key.trim()}`);
                                inputRadio.classList.add('availableRadiobutton');
                                for (var optionkey in data.basePrice[key]) {
                                    // optionsData
                                    var option_id = data.basePrice[key][optionkey].id;
                                    var option_price = data.basePrice[key][optionkey].price;
                                    var option_label = data.basePrice[key][optionkey].label;
                                    inputRadio.setAttribute("data-option-id" + '_' + optionkey, option_id);
                                    inputRadio.setAttribute("data-option-price" + '_' + optionkey, option_price);
                                    inputRadio.setAttribute("data-option-label" + '_' + optionkey, option_label);
                                }
                                var label = document.createElement('label');
                                label.setAttribute('for', 'radio_' + radio_i);
                                label.classList.add('availableRadiolabel');
                                var timeSpan = document.createElement('span');
                                timeSpan.classList.add('time');
                                timeSpan.textContent = time + ' - ' + totalAvailable;
                                label.appendChild(timeSpan);
                                radioGroup.appendChild(inputRadio);
                                radioGroup.appendChild(label);
                                document.getElementById('availability').appendChild(radioGroup);
                                if (!selectedOption) {
                                    if (inputRadio.getAttribute('data-disabled') == "true") {
                                        inputRadio.checked = true;
                                        selectedOption = true;
                                        var options = document.querySelectorAll('.option_price');
                                        if (options.length > 0) {
                                            var option_i = 0;
                                            options.forEach(function(option_price) {
                                                for (var i = 0; i < inputRadio.attributes.length; i++) {
                                                    var attr = inputRadio.attributes[i];
                                                    if (attr.name.startsWith('data-option-label')) {
                                                        var index = attr.name.split('_')[1];
                                                        var optionLabel = attr.value;

                                                        if (option_price.getAttribute('data-label') == optionLabel) {
                                                            option_price.setAttribute('data-original-amount', inputRadio.getAttribute('data-option-price' + '_' + index));
                                                            var basePrice = inputRadio.getAttribute('data-option-price' + '_' + index);
                                                            var basePrice_text = '€' + basePrice + '.00';
                                                            option_price.textContent = basePrice_text;
                                                        }
                                                    }
                                                }
                                                option_i++;
                                            });
                                        }
                                    }
                                } else {
                                    inputRadio.checked = false;
                                }
                            }
                            radio_i++;
                        }
                        if (checkedRadioButton != '') {
                            var checked_inputRadio = document.querySelector('#' + checkedRadioButton);
                            if (checked_inputRadio){
                                if(checked_inputRadio.getAttribute('data-disabled') == "true") {
                                    checked_inputRadio.checked = true;
                                    var options = document.querySelectorAll('.option_price');
                                    if (options.length > 0) {
                                        var option_i = 0;
                                        options.forEach(function(option_price) {
                                            for (var i = 0; i < checked_inputRadio.attributes.length; i++) {
                                                var attr = checked_inputRadio.attributes[i];
                                                if (attr.name.startsWith('data-option-label')) {
                                                    var index = attr.name.split('_')[1];
                                                    var optionLabel = attr.value;
                                                    if (option_price.getAttribute('data-label') == optionLabel) {
                                                        option_price.setAttribute('data-original-amount', checked_inputRadio.getAttribute('data-option-price' + '_' + index));
                                                        var basePrice = checked_inputRadio.getAttribute('data-option-price' + '_' + index);
                                                        var basePrice_text = '€' + basePrice + '.00';
                                                        option_price.textContent = basePrice_text;
                                                    }
                                                }
                                            }
                                            option_i++;
                                        });
                                    }
                                }
                            } 
                            
                            
                        }
                        var radioButtons = document.querySelectorAll('.availableRadiobutton');
                        radioButtons = Array.from(radioButtons).filter((button, index, array) => {
                            return array.findIndex(b => b.id === button.id) === index;
                        });
                        if (radioButtons.length > 0) {
                            var foundChecked = false;
                            radioButtons.forEach(function(radioButton) {
                                if (radioButton.checked) {
                                    foundChecked = true;
                                    var selectedAttribute = radioButton.getAttribute('data-price');
                                    if (selectedAttribute == 0 || selectedAttribute < 0) {
                                        if (isMobileDevice()) {
                                            jQuery('.form-submit').text('No Availability');
                                            jQuery('.form-submit').addClass('disabled');
                                            jQuery('.form-submit').prop('disabled', true);
                                        } else {
                                            buttonSubmit.innerText = 'No Availability';
                                            buttonSubmit.classList.add('disabled');
                                            buttonSubmit.setAttribute('disabled', 'true');
                                        }

                                    } else {
                                        if (radioButton.getAttribute("data-disabled") == "true") {
                                            if (isMobileDevice()) {
                                                jQuery('.form-submit').text('Book now');
                                                jQuery('.form-submit').removeAttr('disabled');
                                                jQuery('.form-submit').removeClass('disabled');
                                            } else {
                                                buttonSubmit.innerText = 'Book now';
                                                buttonSubmit.removeAttribute('disabled');
                                                buttonSubmit.classList.remove('disabled');
                                            }
                                        } else {
                                            if (isMobileDevice()) {
                                                jQuery('.form-submit').text('No availability');
                                                jQuery('.form-submit').addClass('disabled');
                                                jQuery('.form-submit').prop('disabled', true);
                                            } else {
                                                buttonSubmit.innerText = 'No availability';
                                                buttonSubmit.classList.add('disabled');
                                                buttonSubmit.setAttribute('disabled', 'true');
                                            }
                                        }
                                        document.querySelector('.total-price-value').textContent = '€' + selectedAttribute;
                                    }
                                }
                            });
                            if (!foundChecked) {
                                if (isMobileDevice()) {
                                    jQuery('.form-submit').text('No availability');
                                    jQuery('.form-submit').addClass('disabled');
                                    jQuery('.form-submit').prop('disabled', true);
                                } else {
                                    buttonSubmit.innerText = 'No availability';
                                    buttonSubmit.classList.add('disabled');
                                    buttonSubmit.setAttribute('disabled', 'true');
                                }
                                document.querySelector('.total-price-value').textContent = '€0';
                            }
                        } else {
                            if (isMobileDevice()) {
                                jQuery('#selectTimeDiv').css('display', 'none');
                                jQuery('.form-submit').text('No availability');
                                jQuery('.form-submit').addClass('disabled');
                                jQuery('.form-submit').prop('disabled', true);
                            } else {
                                selectTimeDiv.style.display = 'none';
                                buttonSubmit.innerText = 'No availability';
                                buttonSubmit.classList.add('disabled');
                                buttonSubmit.setAttribute('disabled', 'true');
                            }
                        }
                    } else {
                        if (isMobileDevice()) {
                            jQuery('#selectTimeDiv').css('display', 'none');
                            jQuery('.form-submit').text('No availability');
                            jQuery('.form-submit').addClass('disabled');
                            jQuery('.form-submit').prop('disabled', true);
                        } else {
                            selectTimeDiv.style.display = 'none';
                            buttonSubmit.innerText = 'No availability';
                            buttonSubmit.classList.add('disabled');
                            buttonSubmit.setAttribute('disabled', 'true');
                        }
                    }
                    hideLoading();

                })
                .catch(function(error) {
                    return error;
                });
        }
    });

    function validateForm() {

        var buttonSubmit = document.querySelector(".form-submit");
        if (buttonSubmit.classList.contains('disabled')) {
            return false;
        } else {

            var radioButtons = document.querySelectorAll('.availableRadiobutton');
            if (radioButtons.length > 0) {
                radioButtons.forEach(function(radioButton) {
                    if (radioButton.checked) {
                        var schedule_time = radioButton.getAttribute('data-session_time');
                        document.querySelector(".schedule_timeInput").setAttribute("value", schedule_time);
                    }
                });
            }
            var currentURL = window.location.href;
            document.querySelector('#tour_url').value = currentURL;

            if (isMobileDevice()) {
                jQuery('.form-submit').addClass('disabled');
                jQuery('.form-submit').prop('disabled', true);
            } else {
                buttonSubmit.classList.add('disabled');
                buttonSubmit.setAttribute('disabled', true);
            }

            return true;
        }
        }

        function isMobileDevice() {
        return window.innerWidth <= 768; // Adjust the threshold as per your requirement
        }


        function incrementValue(btn) {
        var inputField = btn.parentNode.querySelector('.input-field');
        var qtyChangeSpans = btn.parentNode.querySelectorAll('.qtyChange');
        var value = parseInt(inputField.value, 10);

        inputField.setAttribute('value', value + 1);
        qtyChangeSpans.forEach(function(qtyChangeSpan) {
            qtyChangeSpan.setAttribute("data-qty", inputField.value);
        });
        }

        function decrementValue(btn) {
        var inputField = btn.parentNode.querySelector('.input-field');
        var qtyChangeSpans = btn.parentNode.querySelectorAll('.qtyChange');
        var value = parseInt(inputField.value, 10);
        if (value > 0) {

            inputField.setAttribute('value', value - 1);
            qtyChangeSpans.forEach(function(qtyChangeSpan) {
                qtyChangeSpan.setAttribute("data-qty", inputField.value);
            });

        }
        }


        function validateNumberInput(event) {
        var inputValue = event.target.value;
        var numericValue = inputValue.replace(/\D/g, '');
        event.target.value = numericValue;
        }

        function getAllpriceOptionlabel() {
        var form_flex = document.querySelectorAll(".form-flex.shadow-box:not(.customGroupOption)");

        form_flex.forEach(form_flex_divs => {

            var label = form_flex_divs.querySelector('.label-box');


            if (label.querySelector('.priceOptionlabel').innerHTML.includes("Group")) {
                form_flex_divs.style.display = "none";

                var existingFormFlex = document.querySelector('.customGroupOption');
                if (!existingFormFlex) {

                    var input_priceOption_id_name = label.querySelector('.priceOption_id').getAttribute('name');
                    var input_priceOption_id_value = label.querySelector('.priceOption_id').getAttribute('value');

                    var input_priceOption_label_name = label.querySelector('.priceOption_label').getAttribute('name');
                    var input_priceOption_label_value = label.querySelector('.priceOption_label').getAttribute('value');

                    var p_option_price_currency = label.querySelector('.option_price').getAttribute('data-currency-base');
                    var p_option_price_amount = label.querySelector('.option_price').getAttribute('data-original-amount');
                    var p_option_price_data_value = label.querySelector('.option_price').getAttribute('data-value');
                    var p_option_price_data_label = label.querySelector('.option_price').getAttribute('data-label');
                    var p_option_price_textContent = label.querySelector('.option_price').innerHTML;


                    var div_options_box = form_flex_divs.querySelector('.options-box');
                    var selectSection = div_options_box.querySelector('.quantity');

                    var div_options_boxHTML = form_flex_divs.querySelector('.options-box').innerHTML;



                    // Create the main container div
                    var formFlex = document.createElement('div');
                    formFlex.classList.add('form-flex', 'shadow-box', 'customGroupOption');

                    // Create the label-box div
                    var labelBox = document.createElement('div');
                    labelBox.classList.add('label-box');

                    // Create the input element for priceOption[id]
                    var idInput = document.createElement('input');
                    idInput.setAttribute('type', 'hidden');
                    idInput.classList.add('priceOption_id');
                    idInput.setAttribute('name', '');
                    idInput.setAttribute('value', input_priceOption_id_value);


                    // Create the input element for priceOption[label]
                    var labelInput = document.createElement('input');
                    labelInput.setAttribute('type', 'hidden');
                    labelInput.classList.add('priceOption_label');
                    labelInput.setAttribute('name', '');
                    labelInput.setAttribute('value', input_priceOption_label_value);

                    // Create the h6 element for priceOptionlabel
                    var h6Element = document.createElement('h6');
                    h6Element.classList.add('priceOptionlabel');
                    h6Element.textContent = 'Participants';

                    // Create the p element for price
                    var pElement = document.createElement('p');
                    pElement.classList.add('price', 'option_price');
                    pElement.setAttribute('data-currency-base', p_option_price_currency);
                    pElement.setAttribute('data-original-amount', p_option_price_amount);
                    pElement.setAttribute('data-value', p_option_price_data_value);
                    pElement.setAttribute('data-label', p_option_price_data_label);
                    pElement.textContent = p_option_price_textContent;

                    // Append the input and h6 elements to the label-box div
                    labelBox.appendChild(idInput);
                    labelBox.appendChild(labelInput);
                    labelBox.appendChild(h6Element);
                    labelBox.appendChild(pElement);

                    // Create the options-box div
                    var optionsBox = document.createElement('div');
                    optionsBox.classList.add('options-box');

                    optionsBox.innerHTML = div_options_boxHTML;

                    // Append the label-box and options-box divs to the main container div
                    formFlex.appendChild(labelBox);
                    formFlex.appendChild(optionsBox);

                    // Append the main container div to the document body or another parent element
                    var parent_form_flex = document.querySelector('.parent-form-flex');
                    parent_form_flex.appendChild(formFlex);


                    // make 1 selected
                    var selectElements = document.querySelectorAll('.quantity');
                    var lastSelectElementIndex = selectElements.length - 1; // Index of the last select element

                    if (lastSelectElementIndex >= 0) {
                        selectElements[lastSelectElementIndex].selectedIndex = 1;
                        selectElements[lastSelectElementIndex].setAttribute('name', '');
                    }

                }
            }
        });
        }
        getAllpriceOptionlabel();
</script>
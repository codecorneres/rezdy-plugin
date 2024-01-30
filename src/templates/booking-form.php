<?php defined('ABSPATH') || exit; ?>

<div class="booking-sidebar-widget-box in calendar-widget">
    <div class="booking-inner availability-container">
        <div class="booking-form-list">
            <?php
            //$product->product->productCode;
            if (!session_id()) {
                session_start();
            }
            ?>
            <form action="<?= esc_url(site_url('/checkout/' . session_id())); ?>" method="post" class="session-form" onsubmit="return validateForm()">
                <div class="booking-group">
                    <div class="booking-single">
                        <div class="title">
                            <h5>Enter Number of Participants <span class="required">*</span></h5>
                        </div>
                        <input type="hidden" name="OrderItem[preferredDate]" value="" id="selectedDate">
                        <input type="hidden" name="OrderItem[productCode]" value="<?= $product->product->productCode; ?>" id="productCode">
                        <?php foreach ($priceOptions as $key => $value) { ?>
                            <div class="form-flex">
                                <div class="label-box">
                                    <input type="hidden" name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][priceOption][id]" id="" value="<?= $value->id; ?>">
                                    <h6><?php echo ($value->label == 'Quantity') ? 'Everyone' : $value->label; ?></h6>
                                    <p class="price" data-currency-base="<?php echo $product->product->currency; ?>" data-original-amount="<?php echo $value->price; ?>"><?php echo '€' . $value->price . '.00'; ?></p>
                                </div>
                                <div class="options-box">
                                    <select name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][quantity]" id="" class="quantity">
                                        <?php for ($i = 0; $i <= 20; $i++) : ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                        <option value="21">>20</option>
                                    </select>
                                    <input type="number" name="" id="" class="quantity-input" style="display: none;">
                                </div>


                            </div>
                        <?php  } ?>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Date <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex ">
                                <div class="calendar datepicker-container position-relative">
                                    <div id="datepicker" class="availabilitypicker">

                                    </div>

                                    <div class="rezdy-overlay-loader" style="display: none;"><i class="fa fa-circle-o-notch fa-spin"></i></div>

                                </div>
                            </div>
                        </div>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Time <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex">
                                <select name="schedule_time" id="availability">

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="price-box price-summary">
                        <h5>Price(EUR)</h5>
                        <h4 class="total-price-value">€0</h4>
                    </div>
                    <div class="btn-submit-box">
                        <input type="hidden" name="tour_url" id="tour_url" value="">
                        <button type="submit" class="btn-submit form-submit">Book Now</button>
                    </div>

                    <div class="form-note">
                        <p><b>Please note:</b> After your purchase is confirmed we will email you a confirmation.</p>
                    </div>
            </form>
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
    // if($value->allDay){
    //     $dates[] = date('Y-m-d', strtotime($value->startTimeLocal));
    // }else{
    //     $startTimeLocal = date('Y-m-d', strtotime($value->startTimeLocal));
    //     $dates[] = $startTimeLocal;
    //     $endTimeLocal = date('Y-m-d', strtotime($value->endTimeLocal));
    //     for ($i = 0; $startTimeLocal <  $endTimeLocal; $i++) {
    //         $startTimeLocal = date('Y-m-d', strtotime($startTimeLocal . ' +1 day'));
    //         $dates[] = $startTimeLocal;
    //     }
    // }
}

$dates = array_unique($dates);

?>
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    $(function() {
        var enabledDates = <?php echo json_encode($dates); ?>;
        var enableasdadDates = "<?php echo  date('Y-m-d H:i:s'); ?>";
        let datesArray = Object.keys(enabledDates).map(key => enabledDates[key]);
        console.log(datesArray);

        function enableDates(date) {
            var formattedDate = $.datepicker.formatDate("yy-mm-dd", date);
            return datesArray.indexOf(formattedDate) !== -1;
        }

        var loading = document.querySelector('.rezdy-overlay-loader');
        var buttonSubmit = document.querySelector('.form-submit');

        function showLoading() {
            if (loading) {
                loading.style.display = 'block';
                buttonSubmit.disabled = true;
            }
        }

        function hideLoading() {
            if (loading) {
                loading.style.display = 'none';
                buttonSubmit.disabled = false;
            }
        }
        $("#datepicker").datepicker({
            prevText: "←",
            nextText: "→",
            minDate: 0,
            beforeShowDay: function(date) {
                if (enableDates(date)) {
                    return [true, ""];
                } else {
                    return [false, "disabled"];
                }
            },
            onSelect: function() {
                
                var selectedDate = $.datepicker.formatDate("yy-m-d", $(this).datepicker("getDate"));
                document.querySelector('#selectedDate').value = selectedDate;

                fetching_availabilities();
                fetching_sessions(selectedDate);

            }

        });


        function fetching_availabilities() {
            var form = document.querySelector('.session-form');
            var data = {
                action: 'fetching_availabilities'
            };
            var formData = new FormData(form);
            
            for (var key in data) {
                formData.append(key, data[key]);
            }
            var requestData = {};

            formData.forEach(function(value, key) {
                requestData[key] = value;
            });
            showLoading();
            var response = fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    console.log('here we are!!');
                    console.log(data);
                    var select = document.querySelector("#availability");
                    select.innerHTML = '';
                    var firstDisabled = true;
                    var firstKey;

                    // Initialize selectedOption
                    var selectedOption = false;
                    
                    for (const key in data.sessionTimeLabel) {
                        if (Object.hasOwnProperty.call(data.sessionTimeLabel, key)) {
                            const value = data.sessionTimeLabel[key];
                            const price = data.totalPrice[key];
                            const activeSession = data.activeSession[key];
                            console.log("activeSession " + activeSession);
                            console.log("selectedOption " + selectedOption);
                            var option = document.createElement("option");
                            option.text = `${value}`;
                            option.value = `${key}`;
                            option.setAttribute("data-price", price);
                            option.setAttribute("data-disabled", activeSession);
                            if (activeSession === true && !selectedOption) {

                                option.selected = true;
                                selectedOption = true;
                            } else {
                                option.selected = false;
                                selectedOption = false;
                            }

                            select.add(option);
                        }
                    }
                    var selectedOption = select.options[select.selectedIndex];
                    var selectedValue = select.value;
                    var selectedAttribute = selectedOption.getAttribute('data-price'); // Replace 'data-price' with the desired attribute name

                    var submitButton = document.querySelector(".form-submit"); // Replace with the ID of your submit button

                    if (selectedOption.getAttribute("data-disabled") == "true") {
                        //console.log('first')
                        submitButton.innerText = 'Book now';
                        submitButton.removeAttribute('disabled');
                        submitButton.classList.remove('disabled');
                    } else {
                        //console.log('second')

                        submitButton.innerText = 'No availability';
                        submitButton.classList.add('disabled');
                        submitButton.setAttribute('disabled', true);

                    }

                    document.querySelector('.total-price-value').textContent = '€' + selectedAttribute;

                    hideLoading();

                })
                .catch(function(error) {
                    return error;
                });

        }
        
        function fetching_sessions(selectedDate) {
            showLoading();
            var productCode = document.querySelector('#productCode').value;
            var data = {
                action: 'fetching_sessions',
                productCode: productCode,
                firstDate: selectedDate
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

                    hideLoading();

                })
                .catch(function(error) {
                    return error;
                });
        }

        
    });

    function validateForm() {   
                var submitButton = document.querySelector(".form-submit");
                if(submitButton.classList.contains('disabled')){
                    return false;
                }
                else{
                    var currentURL = window.location.href;
                    document.querySelector('#tour_url').value = currentURL;
                    return true;
                }
        }

</script>
<?php defined('ABSPATH') || exit; ?>

<div class="booking-sidebar-widget-box in calendar-widget">
    <div class="booking-inner availability-container">
        <div class="booking-form-list">
            <form action="" class="session-form">
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
                                    <h6><?php echo $value->label; ?></h6>
                                    <p class="price" data-currency-base="<?php echo $product->product->currency; ?>" data-original-amount="<?php echo $value->price; ?>"><?php echo '€' . $value->price . '.00'; ?></p>
                                </div>
                                <div class="options-box">
                                    <select name="ItemQuantity[<?= $product->product->productCode; ?>][<?= $key; ?>][quantity]" id="" class="quantity">
                                        <?php for ($i = 0; $i <= 20; $i++) : ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                        <option value="21">>20</option>
                                    </select>
                                    <input type="text" name="" id="" class="quantity-input" style="display: none;">
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
                                <select name="" id="availability">

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="price-box price-summary">
                        <h5>Price(EUR)</h5>
                        <h4 class="total-price-value">€0</h4>
                    </div>
                    <div class="btn-submit-box">
                        <button type="button" class="btn-submit form-submit">Book Now</button>
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
}
$dates = array_unique($dates);

?>
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    // var customButton = document.querySelector('.quantity');

    // customButton.addEventListener('click', function() {
    //     var form = document.querySelector('.session-form');

    //     var formData = new FormData(form);
    //     var requestData = {};

    //     formData.forEach(function(value, key) {
    //         requestData[key] = value;
    //     });
    //     console.log(requestData)
    // });
    // window.onload = function() {
    //     var selectElements = document.querySelector('.quantity');
    //     for (var i = 0; i < selectElements.length; i++) {
    //         selectElements[i].selectedIndex = 1; // Set the first option as selected
    //     }
    // };
    // window.onload = function() {

    //     var selectElements = document.querySelectorAll('.quantity');
    //     // Select 'Option B' for the first select element
    //     selectElements[0].selectedIndex = 1;
    // };

    $(function() {
        var enabledDates = <?php echo json_encode($dates); ?>;
        let datesArray = Object.keys(enabledDates).map(key => enabledDates[key]);

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

                // fetching_sessions(selectedDate);
                fetching_availabilities();
            }

        });


        function fetching_availabilities() {
            var form = document.querySelector('.session-form');
            var data = {
                action: 'ajax_action_2'
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
                    var select = document.querySelector("#availability");
                    select.innerHTML = '';
                    data.sessionTimeLabel.map((session) => {
                        var option = document.createElement("option");
                        option.text = `${session}`;
                        option.value = `${session}`;
                        select.add(option);
                    });
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
                action: 'ajax_action',
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
                    // var select = document.querySelector("#availability");
                    // select.innerHTML = '';

                    // for (var key in data) {
                    //     if (data.hasOwnProperty(key)) {
                    //         var date = new Date(selectedDate);
                    //         var formattedDate = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);

                    //         data.availability[formattedDate].map((session) => {
                    //             var date = new Date(session.startTime);
                    //             var time = ("0" + date.getUTCHours()).slice(-2) + ":" + ("0" + date.getUTCMinutes()).slice(-2);
                    //             var option = document.createElement("option");
                    //             option.text = time;
                    //             option.value = `${key} - Available`;
                    //             select.add(option);
                    //             console.log(data, 'datadatadatadata');

                    //         });

                    //     }
                    // }
                    hideLoading();

                })
                .catch(function(error) {
                    return error;
                });
        }

    });
</script>
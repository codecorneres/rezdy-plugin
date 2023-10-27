<?php defined('ABSPATH') || exit; ?>

<div class="booking-sidebar-widget-box in calendar-widget">
    <div class="booking-inner availability-container">
        <div class="booking-form-list">
            <form action="">
                <div class="booking-group">
                    <div class="booking-single">
                        <div class="title">
                            <h5>Enter Number of Participants <span class="required">*</span></h5>
                        </div>
                        <input type="text" name="productCode" value="<?= $product->product->productCode; ?>" id="productCode">
                        <?php foreach ($priceOptions as $key => $value) { ?>
                            <div class="form-flex">
                                <div class="label-box">
                                    <h6><?php echo $value->label; ?></h6>
                                    <p class="price" data-currency-base="<?php echo $product->product->currency; ?>" data-original-amount="<?php echo $value->price; ?>"><?php echo '€' . $value->price . '.00'; ?></p>
                                </div>
                                <div class="options-box">
                                    <select name="quantity" id="" class="quantity">
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
                                <div class="calendar datepicker-container">
                                    <div id="datepicker" class="availabilitypicker"></div>
                                </div>
                            </div>
                        </div>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Time <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex">
                                <select name="" id="availability">
                                    <?php
                                    // foreach ($availabilities as $key => $availability) {
                                    ?>
                                    <!-- <option value="<?php echo date('h:i', strtotime($availability->startTime)) ?>"><?php echo date('h:i', strtotime($availability->startTime)) ?>- Available</option> -->
                                    <?php
                                    // }
                                    ?>
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
    $(function() {
        var enabledDates = <?php echo json_encode($dates); ?>;
        let datesArray = Object.keys(enabledDates).map(key => enabledDates[key]);
        // console.log(datesArray);

        function enableDates(date) {
            var formattedDate = $.datepicker.formatDate("yy-mm-dd", date);
            return datesArray.indexOf(formattedDate) !== -1;
        }


        $("#datepicker").datepicker({
            prevText: "←",
            nextText: "→",
            minDate: 0,
            beforeShowDay: function(date) {
                if (enableDates(date)) {
                    return [true, ""];

                    // } else if (date.toDateString() === new Date().toDateString() && enableDates(date)) {
                    //     return [true, "ui-state-highlight", "Today"];
                } else {
                    return [false, "disabled"];
                }
            },
            onSelect: function() {
                var dateText = $.datepicker.formatDate("MM dd, yy", $(this).datepicker("getDate"));
                console.log(dateText);
            }

        });




        ///
        // $(".quantity").on("click", function() {
        //     var selectedDate = $("#datepicker").datepicker("getDate");
        //     if (selectedDate) {
        //         var formattedDate = $.datepicker.formatDate("yy-mm-dd", selectedDate);
        //         console.log("Selected date: " + formattedDate);
        //     } else {
        //         console.log("No date selected");
        //     }
        // });

        var datePicker = document.getElementById('datepicker');
        var customButton = document.querySelector('.quantity');

        customButton.addEventListener('click', function() {
            var selectedDate = datePicker.value;
            if (selectedDate) {
                console.log('Selected date:', selectedDate);
            } else {
                console.log('No date selected');
            }
        });

    });
</script>
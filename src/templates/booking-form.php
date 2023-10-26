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
                        <?php foreach ($priceOptions as $key => $value) { ?>
                            <div class="form-flex">
                                <div class="label-box">
                                    <h6><?php echo $value->label; ?></h6>
                                    <p class="price" data-currency-base="<?php echo $product->product->currency; ?>" data-original-amount="<?php echo $value->price; ?>"><?php echo '€' . $value->price . '.00'; ?></p>
                                </div>
                                <div class="options-box">
                                    <select name="quantity" id="" class="quantity">
                                        <?php for ($i = 1; $i <= 20; $i++) : ?>
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
                                <select name="" id="">
                                    <?php
                                    foreach ($availabilities as $key => $availability) {
                                    ?>
                                        <option value="<?php echo date('h:i', strtotime($availability->startTime)) ?>"><?php echo date('h:i', strtotime($availability->startTime)) ?>- Available</option>
                                    <?php
                                    }
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
                        <button type="submit" class="btn-submit">Book Now</button>
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
$firstDate = date('Y-m-d', strtotime($availabilities[0]->startTimeLocal));
$lastDate = date('Y-m-d', strtotime($availabilities[count($availabilities) - 1]->endTimeLocal));

?>
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    $(function() {
        var startDate = new Date("<?php echo $firstDate; ?>");
        var endDate = new Date("<?php echo $lastDate; ?>");
        var currentDate = new Date();
        var currentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);


        function enableDates(date) {
            return date >= startDate && date <= endDate;
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

    });
</script>
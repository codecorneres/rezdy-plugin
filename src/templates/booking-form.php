<?php defined('ABSPATH') || exit; ?>

<div class="booking-sidebar-widget-box">
    <div class="booking-inner">
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
                                    <p><?php echo '€' . $value->price . '.00'; ?></p>
                                </div>
                                <div class="options-box">
                                    <select name="" id="">
                                        <option value="">1</option>
                                        <option value="">2</option>
                                        <option value="">3</option>
                                        <option value="">4</option>
                                        <option value="">5</option>
                                        <option value="">6</option>
                                        <option value="">7</option>
                                    </select>
                                </div>
                            </div>
                        <?php  } ?>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Date <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex">
                                <div class="calendar">

                                    <div id="datepicker"></div>
                                </div>
                                <!-- <select name="" id="">
                                    <option value="">09:30 - Available</option>
                                    <option value="">14:30 - Available</option>
                                </select> -->
                            </div>
                        </div>
                        <div class="booking-single">
                            <div class="title">
                                <h5>Choose a Time <span class="required">*</span></h5>
                            </div>
                            <div class="choose-time-form form-flex">
                                <select name="" id="">
                                    <option value="">09:30 - Available</option>
                                    <option value="">14:30 - Available</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="price-box price-summary">
                        <h5>Price(EUR)</h5>
                        <h4>€89.00</h4>
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
$firstDate = date('Y-m-d', strtotime($availability[0]->startTimeLocal));
$lastDate = date('Y-m-d', strtotime($availability[count($availability) - 1]->endTimeLocal)); //$availability[count($availability) - 1]->endTimeLocal;

?>
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script>
    $(function() {
        var startDate = new Date("<?php echo $firstDate; ?>");
        var endDate = new Date("<?php echo $lastDate; ?>");

        function enableDates(date) {
            return date >= startDate && date <= endDate;
        }

        $("#datepicker").datepicker({
            // firstDay: 1,
            // changeMonth: true,
            // changeYear: true,
            prevText: '<i class="fa fa-fw fa-angle-left"></i>',
            nextText: '<i class="fa fa-fw fa-angle-right"></i>',
            beforeShowDay: function(date) {
                if (enableDates(date)) {
                    return [true, ""];
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
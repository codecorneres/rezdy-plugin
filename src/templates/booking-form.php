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
                                <p><?php echo '€'.$value->price.'.00'; ?></p>
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

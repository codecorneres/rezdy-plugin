jQuery(function () {
    jQuery(document).on('click', '.change_pass', function () {
        //console.log('change_pass clicked');
        jQuery('.login_password').remove();

        jQuery('<tr class="old_password">').append(
            jQuery('<th scope="row"><label for="old_password">Old Password</label></th>'),
            jQuery('<td><input name="old_password" value="" type="password" id="old_password" class="regular-text"></td>')
        ).appendTo('tbody');

        jQuery('<tr class="new_password">').append(
            jQuery('<th scope="row"><label for="new_password">New Password</label></th>'),
            jQuery('<td><input name="new_password" value="" type="password" id="new_password" class="regular-text"></td>')
        ).appendTo('tbody');

        jQuery(this).removeClass('change_pass').addClass('back_to_login');
        jQuery('.password_type').attr('name', 'change_password');
        jQuery(this).text('Back');
    });

    jQuery(document).on('click', '.back_to_login', function () {
        //console.log('back_to_login clicked');
        jQuery('.old_password, .new_password').remove();

        jQuery('<tr class="login_password">').append(
            jQuery('<th scope="row"><label for="rezdy_auth_pass">Enter Password</label></th>'),
            jQuery('<td><input name="rezdy_auth_pass" value="" type="password" id="rezdy_auth_pass" class="regular-text"></td>')
        ).appendTo('tbody');

        jQuery(this).removeClass('back_to_login').addClass('change_pass');
        jQuery('.password_type').attr('name', 'rezdy_password');
        jQuery(this).text('Change Password');
    });

    jQuery(document).on('click', '.notice-dismiss', function () {
        //console.log('back_to_login clicked');
        jQuery('div.is-dismissible').remove();
    });
});

function validatePasswordFields() {
    var old_password = jQuery('#old_password').val();
    var new_password = jQuery('#new_password').val();

    if (old_password == '') {
        var errorDiv = jQuery('<div class="notice is-dismissible error"><p>Please enter old Password.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
        jQuery('.page_custom_head').before(errorDiv);
        return false;
    }
    if (new_password == '') {
        var errorDiv = jQuery('<div class="notice is-dismissible error"><p>Please enter new Password.</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
        jQuery('.page_custom_head').before(errorDiv);
        return false;
    }

    return true;

}
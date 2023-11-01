// form for accordian type details data

$(document).ready(function () {
    $('.toggle').each(function () {
        $(this).on('click', function () {
            $(this).parents('.Billing_Contact').toggleClass("intro");
            $(this).siblings('.content').toggle();
        });
    });
});
 



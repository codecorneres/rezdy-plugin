$(document).ready(function () {
    $('body').on('change', '.quantity', function () {
        var selectedValue = parseInt($(this).val());

        if (selectedValue > 20) {
            $(this).hide();
            var inputElement = $(this).siblings('.quantity-input');
            inputElement.show().focus();
            recalculateTotalPrice(inputElement.val());

        } else {
            $(this).show();
            $(this).siblings('.quantity-input').hide();
            recalculateTotalPrice($(this).val());
        }
    });

    $('body').on('keyup', '.quantity-input', function () {
        recalculateTotalPrice($(this).val());
    });

    function recalculateTotalPrice(value) {
        var total = 0;

        $('.quantity').each(function () {
            var quantity = $(this).is(':visible') ? parseInt($(this).val()) : parseInt(value);
            var price = $(this).closest('.form-flex').find('.price').data('original-amount');
            var itemTotal = quantity * price;
            total += itemTotal;
        });

        var currencyCode = $('.form-flex:first .price').data('currency-base');
        var currencySymbol = getCurrencySymbol(currencyCode);
        console.log('Total Price for all items: ' + total);
        console.log('Total Price for all items: ' + total.toFixed(2));
        // if (total.isNumeric)

        if (!isNaN(total)) {
            $('.total-price-value').text(currencySymbol + total.toFixed(2));

        } else {
            total = 0;
            $('.total-price-value').text(currencySymbol + total.toFixed(2));

        }
    }

    function getCurrencySymbol(currencyCode) {
        const currencySymbols = {
            USD: '$',
            EUR: '€',
            GBP: '£',
            JPY: '¥',
        };

        return currencySymbols[currencyCode] || currencyCode;
    }
});
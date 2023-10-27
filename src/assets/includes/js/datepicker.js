document.addEventListener('DOMContentLoaded', function () {


    console.log(123);
    var datePicker = document.querySelector('#datepicker');
    var productCode = document.querySelector('#productCode').value;
    var selectedDate = datePicker.value;
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
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            var select = document.querySelector("#availability");
            select.innerHTML = '';
            console.log(data, 'datadatadatadata');
            for (var key in data) {
                if (data.hasOwnProperty(key)) {

                    var date = new Date(selectedDate);
                    var formattedDate = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);

                    data.availability[formattedDate].map((session) => {
                        var date = new Date(session.startTime);
                        var time = ("0" + date.getUTCHours()).slice(-2) + ":" + ("0" + date.getUTCMinutes()).slice(-2);
                        var option = document.createElement("option");
                        option.text = time;
                        option.value = `${key} - Available`;
                        select.add(option);

                    });

                }
            }

        })
        .catch(function (error) {
            return error;
        });


    document.body.addEventListener('change', async function (event) {
        var target = event.target;


        if (target.classList.contains('quantity')) {
            var selectedValue = parseInt(target.value);

            if (selectedValue > 20) {
                target.style.display = 'none';
                var inputElement = target.nextElementSibling;
                inputElement.style.display = 'block';
                inputElement.focus();
                recalculateTotalPrice(inputElement.value);

            } else {

                var datePicker = document.querySelector('#datepicker');
                var productCode = document.querySelector('#productCode').value;
                var selectedDate = datePicker.value;
                var data = {
                    action: 'ajax_action',
                    productCode: productCode,
                    firstDate: selectedDate
                };

                var formData = new FormData();
                for (var key in data) {
                    formData.append(key, data[key]);
                }
                var response = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        return data;
                    })
                    .catch(function (error) {
                        return error;
                    });

                var select = document.querySelector("#availability");
                select.innerHTML = '';

                for (var key in response) {


                    if (response.hasOwnProperty(key)) {

                        var date = new Date(selectedDate);
                        var formattedDate = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);

                        response.availability[formattedDate].map((session) => {
                            var date = new Date(session.startTime);
                            var time = ("0" + date.getUTCHours()).slice(-2) + ":" + ("0" + date.getUTCMinutes()).slice(-2);
                            var option = document.createElement("option");
                            option.text = time;
                            option.value = `${key} - Available`;
                            select.add(option);

                        });

                    }
                }




                target.style.display = 'block';
                target.nextElementSibling.style.display = 'none';
                recalculateTotalPrice(target.value);
            }
        }
    });

    document.body.addEventListener('keyup', function (event) {
        var target = event.target;
        if (target.classList.contains('quantity-input')) {
            recalculateTotalPrice(target.value);
        }
    });

    function recalculateTotalPrice(value) {
        var total = 0;
        var quantities = document.querySelectorAll('.quantity');

        quantities.forEach(function (quantity) {
            var currentQuantity = quantity.style.display === 'none' ? parseInt(value) : parseInt(quantity.value);
            var price = parseFloat(quantity.closest('.form-flex').querySelector('.price').dataset.originalAmount);
            var itemTotal = currentQuantity * price;
            total += itemTotal;
        });

        var currencyCode = document.querySelector('.form-flex:first .price').dataset.currencyBase;
        var currencySymbol = getCurrencySymbol(currencyCode);
        console.log('Total Price for all items: ' + total);
        console.log('Total Price for all items: ' + total.toFixed(2));

        if (!isNaN(total)) {
            document.querySelector('.total-price-value').textContent = currencySymbol + total.toFixed(2);

        } else {
            total = 0;
            document.querySelector('.total-price-value').textContent = currencySymbol + total.toFixed(2);

        }
    }

    function getCurrencySymbol(currencyCode) {
        var currencySymbols = {
            USD: '$',
            EUR: '€',
            GBP: '£',
            JPY: '¥',
        };

        return currencySymbols[currencyCode] || currencyCode;
    }
});
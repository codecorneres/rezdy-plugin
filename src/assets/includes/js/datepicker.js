document.addEventListener('DOMContentLoaded', function () {
    var loading = document.querySelector('.rezdy-overlay-loader');
    var buttonSubmit = document.querySelector('.form-submit');
    var selectElements = document.querySelectorAll('.quantity');
    selectElements[0].selectedIndex = 1;


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
    var datePicker = document.querySelector('#datepicker');
    var productCode = document.querySelector('#productCode').value;
    var selectedDate = datePicker.value;

    document.querySelector('#selectedDate').value = selectedDate;



    fetching_sessions(selectedDate);
    fetching_availabilities();



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

                fetching_sessions(selectedDate);
                fetching_availabilities();
                target.style.display = 'block';
                target.nextElementSibling.style.display = 'none';
                recalculateTotalPrice(target.value);
            }
        }
    });

    document.body.addEventListener('keyup', function (event) {
        var target = event.target;
        if (target.classList.contains('quantity-input')) {
            // recalculateTotalPrice(target.value);
            var datePicker = document.querySelector('#datepicker');
            var productCode = document.querySelector('#productCode').value;
            var selectedDate = datePicker.value;

            fetching_sessions(selectedDate);
            fetching_availabilities();
        }
    });

    function recalculateTotalPrice(value) {
        var total = 0;

        // var quantities = document.querySelectorAll('.quantity');

        // quantities.forEach(function (quantity) {
        //     var currentQuantity = quantity.style.display === 'none' ? parseInt(value) : parseInt(quantity.value);
        //     var price = parseFloat(quantity.closest('.form-flex').querySelector('.price').dataset.originalAmount);
        //     var itemTotal = currentQuantity * price;
        //     total += itemTotal;
        // });

        // // var currencyCode = document.querySelector('.form-flex:first .price').dataset.currencyBase;
        // var currencyCode = document.querySelector('.form-flex .price').getAttribute('data-currency-base');

        // var currencySymbol = getCurrencySymbol(currencyCode);
        // console.log('Total Price for all items: ' + total);
        // console.log('Total Price for all items: ' + total.toFixed(2));

        // if (!isNaN(total)) {
        //     document.querySelector('.total-price-value').textContent = currencySymbol + total.toFixed(2);

        // } else {
        //     total = 0;
        //     document.querySelector('.total-price-value').textContent = currencySymbol + total.toFixed(2);

        // }
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

        formData.forEach(function (value, key) {
            requestData[key] = value;
        });
        showLoading();
        var response = fetch(ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                // console.log(data)
                // var select = document.querySelector("#availability");
                // select.innerHTML = '';
                // for (const key in data.sessionTimeLabel) {
                //     if (Object.hasOwnProperty.call(data.sessionTimeLabel, key)) {
                //         const value = data.sessionTimeLabel[key];
                //         const price = data.totalPrice[key];
                //         const activeSession = data.activeSession[key];
                //         var option = document.createElement("option");
                //         option.text = `${value}`;
                //         option.value = `${key}`;
                //         option.setAttribute("data-price", price);
                //         option.setAttribute("data-disabled", activeSession);
                //         select.add(option);
                //     }
                // }

                var select = document.querySelector("#availability");
                select.innerHTML = '';
                var selectedOption = false; // Variable to track if any option is selected

                for (const key in data.sessionTimeLabel) {
                    if (Object.hasOwnProperty.call(data.sessionTimeLabel, key)) {
                        const value = data.sessionTimeLabel[key];
                        const price = data.totalPrice[key];
                        const activeSession = data.activeSession[key];

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



                console.log(selectedAttribute);
                document.querySelector('.total-price-value').textContent = '€' + selectedAttribute;
                console.log(select.value);


                hideLoading();

            })
            .catch(function (error) {
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
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                hideLoading();

            })
            .catch(function (error) {
                return error;
            });
    }




    var select = document.querySelector("#availability");
    var submitButton = document.querySelector(".form-submit"); // Replace with the ID of your submit button

    select.addEventListener("change", function () {
        var selectedOption = select.options[select.selectedIndex];
        var selectedAttribute = selectedOption.getAttribute('data-price');
        document.querySelector('.total-price-value').textContent = '€' + selectedAttribute;
        console.log(selectedOption.getAttribute("data-disabled"));
        if (selectedOption.getAttribute("data-disabled") == "true") {
            console.log('first')
            submitButton.innerText = 'Book now';
            submitButton.removeAttribute('disabled');
            submitButton.classList.remove('disabled');
        } else {
            console.log('second')

            submitButton.innerText = 'No availability';
            submitButton.classList.add('disabled');
            submitButton.setAttribute('disabled', true);

        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    var rezdy_currency_symbol =  decodeURIComponent(window.getCookie('rezdy_currency_symbol'))

    var checkedRadioButton = '';
    var loading = document.querySelector('.rezdy-overlay-loader');
    var buttonSubmit = document.querySelector('.form-submit');
    var plusMinusButtons = document.querySelectorAll('.qtybutton');
    var selectElements = document.querySelectorAll('.quantity');
    if (selectElements[0])
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

        }
    }
    var datePicker = document.querySelector('.availabilitypicker');
    if (datePicker) {
        var selectedDate = datePicker.value;
        document.querySelector('#selectedDate').value = selectedDate;
        fetching_availabilities(checkedRadioButton);
    }


    function fetching_availabilities(checkedRadioButton) {
        buttonSubmit.disabled = true;
        var form = document.querySelector('#session-form');
        var data = {
            action: 'fetching_availabilities'
        };
        var formData = new FormData(form);
        for (var key in data) {
            formData.append(key, data[key]);
        }

        showLoading();
        var response = fetch(ajax_object.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                document.getElementById('availability').innerHTML = '';
                var selectedOption = false;
                if (data.sessionTimeLabel) {
                    var radio_i = 1;
                    for (const key in data.sessionTimeLabel) {
                        if (Object.hasOwnProperty.call(data.sessionTimeLabel, key)) {
                            const value = data.sessionTimeLabel[key];
                            // var totalAvailable = value.split(' ')[2] + ' ' + value.split(' ')[3];
                            var totalAvailable = format_availability_label(value)
                            var time = value.split(' ')[0];
                            var [hour, minute] = time.split(':').map(Number);
                            minute = (minute < 10 ? "0" + minute : minute);
                            var period = hour < 12 ? 'AM' : 'PM';
                            time = hour + ":" + minute + " " + period;
                            var price = data.totalPrice[key];
                            var activeSession = data.activeSession[key];
                            var radioGroup = document.createElement('div');
                            radioGroup.classList.add('radio-group');

                            var inputRadio = document.createElement('input');
                            inputRadio.setAttribute('type', 'radio');
                            inputRadio.setAttribute('name', 'radio');
                            inputRadio.setAttribute('id', 'radio_' + radio_i);
                            inputRadio.setAttribute("data-price", price);
                            inputRadio.setAttribute("data-disabled", activeSession);
                            inputRadio.setAttribute("data-session_time", `${key.trim()}`);

                            for (var optionkey in data.basePrice[key]) {
                                var option_id = data.basePrice[key][optionkey].id;
                                var option_price = data.basePrice[key][optionkey].price;
                                var option_label = data.basePrice[key][optionkey].label;
                                inputRadio.setAttribute("data-option-id" + '_' + optionkey, option_id);
                                inputRadio.setAttribute("data-option-price" + '_' + optionkey, option_price);
                                inputRadio.setAttribute("data-option-label" + '_' + optionkey, option_label);


                            }
                            inputRadio.classList.add('availableRadiobutton');
                            var label = document.createElement('label');
                            label.setAttribute('for', 'radio_' + radio_i);
                            label.classList.add('availableRadiolabel');

                            var timeSpan = document.createElement('span');
                            timeSpan.classList.add('time');
                            timeSpan.textContent = time + totalAvailable;
                            label.appendChild(timeSpan);
                            radioGroup.appendChild(inputRadio);
                            radioGroup.appendChild(label);
                            document.getElementById('availability').appendChild(radioGroup);
                            if (!selectedOption) {
                                if (inputRadio.getAttribute('data-disabled') == "true") {
                                    inputRadio.checked = true;
                                    selectedOption = true;
                                    var options = document.querySelectorAll('.option_price');
                                    if (options.length > 0) {
                                        var option_i = 0;
                                        options.forEach(function (option_price) {
                                            for (var i = 0; i < inputRadio.attributes.length; i++) {
                                                var attr = inputRadio.attributes[i];
                                                if (attr.name.startsWith('data-option-label')) {
                                                    var index = attr.name.split('_')[1];
                                                    var optionLabel = attr.value;
                                                    if (option_price.getAttribute('data-label') == optionLabel) {
                                                        option_price.setAttribute('data-original-amount', inputRadio.getAttribute('data-option-price' + '_' + index));
                                                        var basePrice = inputRadio.getAttribute('data-option-price' + '_' + index);
                                                        var basePrice_text = rezdy_currency_symbol + basePrice;
                                                        option_price.textContent = basePrice_text;
                                                        // var new_basePrice = window.convertCurrency(basePrice)
                                                        // option_price.textContent = `${new_basePrice[0]}${new_basePrice[1]}`
                                                    }
                                                }
                                            }
                                            option_i++;
                                        });
                                    }
                                }
                            } else {
                                inputRadio.checked = false;
                            }
                        }
                        radio_i++;
                    }
                    if (checkedRadioButton != '') {
                        var checked_inputRadio = document.querySelector('#' + checkedRadioButton);
                        if (checked_inputRadio && checked_inputRadio.getAttribute('data-disabled') == "true") {
                            checked_inputRadio.checked = true;
                            var options = document.querySelectorAll('.option_price');
                            if (options.length > 0) {
                                var option_i = 0;
                                options.forEach(function (option_price) {
                                    for (var i = 0; i < checked_inputRadio.attributes.length; i++) {
                                        var attr = checked_inputRadio.attributes[i];
                                        if (attr.name.startsWith('data-option-label')) {
                                            var index = attr.name.split('_')[1];
                                            var optionLabel = attr.value;
                                            if (option_price.getAttribute('data-label') == optionLabel) {
                                                option_price.setAttribute('data-original-amount', checked_inputRadio.getAttribute('data-option-price' + '_' + index));
                                                var basePrice = checked_inputRadio.getAttribute('data-option-price' + '_' + index);
                                                var basePrice_text = rezdy_currency_symbol + basePrice;
                                                option_price.textContent = basePrice_text;
                                                // var new_basePrice = window.convertCurrency(basePrice)
                                                // option_price.textContent = `${new_basePrice[0]}${new_basePrice[1]}`
                                            }
                                        }
                                    }
                                    option_i++;
                                });
                            }
                        }
                    }
                    var selectTimeDiv = document.getElementById('selectTimeDiv');
                    var radioButtons = document.querySelectorAll('.availableRadiobutton');
                    radioButtons = Array.from(radioButtons).filter((button, index, array) => {
                        return array.findIndex(b => b.id === button.id) === index;
                    });
                    if (radioButtons.length > 0) {
                        var foundChecked = false;
                        radioButtons.forEach(function (radioButton) {
                            if (radioButton.checked) {
                                foundChecked = true;
                                var selectedAttribute = radioButton.getAttribute('data-price');
                                if (selectedAttribute == 0 || selectedAttribute < 0) {
                                    buttonSubmit.innerText = 'No Availability';
                                    buttonSubmit.classList.add('disabled');
                                    buttonSubmit.setAttribute('disabled', 'true');
                                } else {
                                    if (radioButton.getAttribute("data-disabled") == "true") {
                                        buttonSubmit.innerText = 'Book now';
                                        buttonSubmit.removeAttribute('disabled');
                                        buttonSubmit.classList.remove('disabled');
                                    } else {
                                        buttonSubmit.innerText = 'No availability';
                                        buttonSubmit.classList.add('disabled');
                                        buttonSubmit.setAttribute('disabled', 'true');
                                    }
                                }
                                document.querySelector('.total-price-value').textContent = rezdy_currency_symbol + selectedAttribute;
                                // const newSelectedAttribute = window.convertCurrency(selectedAttribute)
                                // document.querySelector('.total-price-value').textContent = `${newSelectedAttribute[0]}${newSelectedAttribute[1]}`
                                // document.querySelector('.price-summary h5').textContent = `${window.change_symbol_to_text(newSelectedAttribute[0])}`
                            }
                            if (!foundChecked) {
                                buttonSubmit.innerText = 'No availability';
                                buttonSubmit.classList.add('disabled');
                                buttonSubmit.setAttribute('disabled', 'true');
                                document.querySelector('.total-price-value').textContent = rezdy_currency_symbol + '0';
                                // const newSelectedAttribute = window.convertCurrency(selectedAttribute)
                                // document.querySelector('.total-price-value').textContent = `${newSelectedAttribute[0]}0`;
                                // document.querySelector('.price-summary h5').textContent = `${window.change_symbol_to_text(newSelectedAttribute[0])}`
                            }
                        });
                    } else {
                        selectTimeDiv.style.display = 'none';
                        buttonSubmit.innerText = 'No availability';
                        buttonSubmit.classList.add('disabled');
                        buttonSubmit.setAttribute('disabled', 'true');
                    }
                } else {
                    selectTimeDiv.style.display = 'none';
                    buttonSubmit.innerText = 'No availability';
                    buttonSubmit.classList.add('disabled');
                    buttonSubmit.setAttribute('disabled', 'true');
                }
                if (plusMinusButtons.length > 0) {
                    plusMinusButtons.forEach(function (button) {
                        button.disabled = false;
                        button.classList.remove('disabled');
                    });
                }
                hideLoading();
            })
            .catch(function (error) {
                return error;
            });
    }

    function format_availability_label(value) {
        let label = ''
        const availableNumber = value.split(' ')[2]
        const availableText = value.split(' ')[3]

        if (availableNumber == 'Sold' || Number(availableNumber) < 9) {
            label = ' - ' + availableNumber + ' ' + availableText
        }

        return label
    }

    document.addEventListener('click', function (e) {

        var target = e.target;

        if (target.classList.contains('availableRadiolabel')) {

            var inputId = target.getAttribute('for');
            var inputElement = document.getElementById(inputId);
            var data_disabled = inputElement.getAttribute('data-disabled');

            var options = document.querySelectorAll('.option_price');
            if (options.length > 0) {
                var option_i = 0;
                options.forEach(function (option_price) {

                    for (var i = 0; i < inputElement.attributes.length; i++) {
                        var attr = inputElement.attributes[i];
                        if (attr.name.startsWith('data-option-label')) {
                            var index = attr.name.split('_')[1];
                            var optionLabel = attr.value;

                            if (option_price.getAttribute('data-label') == optionLabel) {
                                option_price.setAttribute('data-original-amount', inputElement.getAttribute('data-option-price' + '_' + index));
                                var basePrice = inputElement.getAttribute('data-option-price' + '_' + index);
                                var basePrice_text = window.rezdy_currency_symbol + basePrice
                                option_price.textContent = basePrice_text;
                            }
                        }
                    }
                    option_i++;
                });
            }
            inputElement.checked = true;

            var selectedAttribute = inputElement.getAttribute('data-price');
            // const newSelectedAttributeInitial = window.convertCurrency(selectedAttribute)
            // const totalPriceValueNew = `${newSelectedAttributeInitial[0]}${newSelectedAttributeInitial[1]}`
            // document.querySelector('.total-price-value').textContent = totalPriceValueNew
            // document.querySelector('.price-summary h5').textContent = `${window.change_symbol_to_text(newSelectedAttributeInitial[0])}`
            if (data_disabled == 'true') {
                document.querySelector('.total-price-value').textContent = rezdy_currency_symbol + selectedAttribute;
            } else {
                document.querySelector('.total-price-value').textContent = rezdy_currency_symbol + '0';
            }

            if (selectedAttribute == 0 || selectedAttribute < 0) {

                buttonSubmit.innerText = 'No availability';
                buttonSubmit.classList.add('disabled');
                buttonSubmit.setAttribute('disabled', 'true');

            } else {

                if (inputElement.getAttribute("data-disabled") == "true") {
                    buttonSubmit.innerText = 'Book now';
                    buttonSubmit.removeAttribute('disabled');
                    buttonSubmit.classList.remove('disabled');
                } else {
                    buttonSubmit.innerText = 'No availability';
                    buttonSubmit.classList.add('disabled');
                    buttonSubmit.setAttribute('disabled', 'true');

                }
            }


        }


    });

    document.addEventListener('change', function (event) {
        var target = event.target;

        if (target.classList.contains('quantity')) {


            var selectedValue = parseInt(target.value);
            if (buttonSubmit) {
                buttonSubmit.classList.add('disabled');
                buttonSubmit.setAttribute('disabled', true);
            }
            if (selectedValue > 20) {
                target.style.display = 'none';
                var inputElement = target.nextElementSibling;
                inputElement.style.display = 'block';
                inputElement.value = parseInt(21);
                inputElement.focus();

                optionSelectForGroupOption(selectedValue);
                var radioButtons = document.querySelectorAll('.availableRadiobutton');
                if (radioButtons.length > 0) {
                    radioButtons.forEach(function (radioButton) {
                        if (radioButton.checked) {
                            checkedRadioButton = radioButton.getAttribute('id');
                        }
                    });
                }
                fetching_availabilities(checkedRadioButton);


            } else if (selectedValue <= 0) {
                var form_flex = document.querySelectorAll(".form-flex.shadow-box:not(.customGroupOption)");
                var foundOptionSelected = false;
                form_flex.forEach(form_flex_divs => {
                    var options_box = form_flex_divs.querySelector('.options-box');
                    if (options_box.querySelector('.quantity').selectedIndex > 0) {
                        foundOptionSelected = true;
                    }
                });
                if (!foundOptionSelected) {
                    buttonSubmit.innerText = 'No availability';
                    buttonSubmit.classList.add('disabled');
                    buttonSubmit.setAttribute('disabled', 'true');
                    // const newSelectedValue = window.convertCurrency(selectedValue)
                    document.querySelector('.total-price-value').textContent = rezdy_currency_symbol + selectedValue;
                    // document.querySelector('.total-price-value').textContent = `${newSelectedValue[0]}${newSelectedValue[1]}`
                    // document.querySelector('.price-summary h5').textContent = `${window.change_symbol_to_text(newSelectedValue[0])}`

                    var radioButtons = document.querySelectorAll('.availableRadiobutton');
                    if (radioButtons.length > 0) {
                        radioButtons.forEach(function (radioButton) {
                            radioButton.setAttribute('data-price', selectedValue);
                        });
                    }
                } else {
                    var radioButtons = document.querySelectorAll('.availableRadiobutton');
                    if (radioButtons.length > 0) {
                        radioButtons.forEach(function (radioButton) {
                            if (radioButton.checked) {
                                checkedRadioButton = radioButton.getAttribute('id');
                            }
                        });
                    }

                    fetching_availabilities(checkedRadioButton);
                    target.style.display = 'block';
                    target.nextElementSibling.style.display = 'none';
                }

            } else {
                var x = parseInt(target.value);
                optionSelectForGroupOption(x);
                var radioButtons = document.querySelectorAll('.availableRadiobutton');
                if (radioButtons.length > 0) {
                    radioButtons.forEach(function (radioButton) {
                        if (radioButton.checked) {
                            checkedRadioButton = radioButton.getAttribute('id');
                        }
                    });
                }

                fetching_availabilities(checkedRadioButton);
            }

            // if (isForPrivateBooking()) {
                const selectedOption = target.options[target.selectedIndex]
                const isOptionForPrivate = selectedOption.getAttribute("data-option-private")

                if (isOptionForPrivate && selectedOption.hasAttribute('data-option-private') && ! window.enquiryFormOpened) {
                    const enquiryForm = window.themeSettings.privateEnquiryForm || 'https://forms.monday.com/forms/82c28ac1265613e8147497620524371a?r=use1'
                    window.open(enquiryForm, '_blank')
                    window.enquiryFormOpened = true
                }
            // }
        }
    });

    function isForPrivateBooking() {
        const params = new URLSearchParams(window.location.search)
        const domainClassesMap = {
            'romewithchef.com': [
                'postid-7686',
                'postid-10092',
                'postid-7013',
                'postid-7007',
                'postid-9400',
                'postid-9399'
            ],
            'thetipsytours.com': [
                'postid-2681',
                'postid-1190'
            ],
            'jackrippertour.com': [
                'elementor-page-6139',
                'elementor-page-5138'
            ]
        }
        const requiredClasses = domainClassesMap[window.location.hostname] || []

        return params.has("private_booking_rezdy") || requiredClasses.some(cls => document.body.classList.contains(cls))
    }

    // Add exceed option for private bookings
    const observer = new MutationObserver(() => {
        document.querySelectorAll("select.quantity").forEach(select => {
            // if (! isForPrivateBooking()) return

            if (select.dataset.processed) return
            select.dataset.processed = "true"

            const formFlex = select.closest(".form-flex")
            if (! formFlex) return

            const labelEl = formFlex.querySelector(".priceOptionlabel")
            if (! labelEl) return

            const labelText = labelEl.innerText.trim()
            if (labelText === "Participants" || labelText === "Everyone" || labelText === "Adult" || labelText === "Adults") {
                const options = select.querySelectorAll("option")
                if (options.length === 0) return

                const lastOption = options[options.length - 1]
                const lastValue = lastOption.value
                const lastText = lastOption.textContent.trim()

                if (! lastText.startsWith(">")) {
                    const newOption = document.createElement("option")
                    newOption.value = lastValue
                    newOption.textContent = ">" + lastText
                    newOption.setAttribute("data-option-private", "true")
                    select.appendChild(newOption)
                } else {
                    lastOption.setAttribute("data-option-private", "true")
                }
            }
        })
    })
    observer.observe(document.body, { childList: true, subtree: true })
    window.enquiryFormOpened = false

    function optionSelectForGroupOption(x) {
        var customGroupOption = document.querySelector('.customGroupOption');
        if (customGroupOption) {

            var custom_label_box = customGroupOption.querySelector('.label-box');
            var custom_option_box = customGroupOption.querySelector('.options-box');

            var form_flex = document.querySelectorAll(".form-flex.shadow-box:not(.customGroupOption)");
            var found = false;
            form_flex.forEach(form_flex_divs => {
                var label = form_flex_divs.querySelector('.label-box');
                var options_box = form_flex_divs.querySelector('.options-box');

                var valueHTML = label.querySelector('.priceOptionlabel').innerHTML;
                var selectedStatus = getGroupValue(x, valueHTML);
                if (selectedStatus) {

                    found = true;

                    //select group option
                    if (x > 21) {

                        var quantitySelect = options_box.querySelector('.quantity');


                        var optionToUpdate = Array.from(quantitySelect.options).find(option => option.getAttribute('data-value') === '21');
                        if (optionToUpdate) {
                            optionToUpdate.value = parseInt(x);
                        }
                        options_box.querySelector('.quantity').selectedIndex = 21;

                    } else {
                        options_box.querySelector('.quantity').selectedIndex = x;
                    }


                    //input priceOption_id get
                    var input_priceOption_id_value = label.querySelector('.priceOption_id').getAttribute('value');

                    //input priceOption_id update
                    custom_label_box.querySelector('.priceOption_id').setAttribute('value', input_priceOption_id_value);


                    //input priceOption_label get
                    var input_priceOption_label_value = label.querySelector('.priceOption_label').getAttribute('value');

                    //input priceOption_label update
                    custom_label_box.querySelector('.priceOption_label').setAttribute('value', input_priceOption_label_value);



                    //P tag get
                    var p_option_price_currency = label.querySelector('.option_price').getAttribute('data-currency-base');
                    var p_option_price_amount = label.querySelector('.option_price').getAttribute('data-original-amount');
                    var p_option_price_data_value = label.querySelector('.option_price').getAttribute('data-value');
                    var p_option_price_data_label = label.querySelector('.option_price').getAttribute('data-label');
                    var p_option_price_textContent = label.querySelector('.option_price').innerHTML;


                    //P tag update
                    custom_label_box.querySelector('.option_price').setAttribute('data-currency-base', p_option_price_currency);
                    custom_label_box.querySelector('.option_price').setAttribute('data-original-amount', p_option_price_amount);
                    custom_label_box.querySelector('.option_price').setAttribute('data-value', p_option_price_data_value);
                    custom_label_box.querySelector('.option_price').setAttribute('data-label', p_option_price_data_label);
                    custom_label_box.querySelector('.option_price').innerHTML = p_option_price_textContent;


                } else {
                    options_box.querySelector('.quantity').selectedIndex = 0;
                }
            });

        }
    }

    document.addEventListener('keyup', function (event) {
        var target = event.target;

        if (target.classList.contains('quantity-input')) {

            var quantity_inputs = document.querySelectorAll('.quantity-input');
            var customGroupOption = document.querySelector('.customGroupOption');
            if (quantity_inputs[0] === target || customGroupOption) { // Compare using ===
                if (+target.value < 1) { // Ensure value is a number
                    target.value = '1'; // Reset value to 1 if less than 1 is typed
                } else {
                    var quantityRequiredMaxValue = document.querySelector('.quantityRequiredMax').value;
                    quantityRequiredMaxValue = parseInt(quantityRequiredMaxValue, 10);
                    if (quantityRequiredMaxValue != NaN && quantityRequiredMaxValue <= parseInt(target.value, 10)) {
                        target.value = quantityRequiredMaxValue;
                    }
                }
            }

            buttonSubmit.classList.add('disabled');
            buttonSubmit.setAttribute('disabled', true);

            if (!customGroupOption) {
                var parentDiv = target.closest('.options-box');
                var quantitySelect = parentDiv.querySelector('.quantity');
                if (quantitySelect.name != '') {
                    target.setAttribute('name', quantitySelect.name);
                    quantitySelect.setAttribute('name', '');
                }

                var optionToUpdate = Array.from(quantitySelect.options).find(option => option.getAttribute('data-value') === '21');
                if (optionToUpdate) {
                    optionToUpdate.value = parseInt(target.value);

                    var radioButtons = document.querySelectorAll('.availableRadiobutton');
                    if (radioButtons.length > 0) {

                        radioButtons.forEach(function (radioButton) {
                            if (radioButton.checked) {
                                checkedRadioButton = radioButton.getAttribute('id');
                            }
                        });
                    }


                    fetching_availabilities(checkedRadioButton);
                }
            } else {



                var x = parseInt(target.value);

                optionSelectForGroupOption(x);


                var radioButtons = document.querySelectorAll('.availableRadiobutton');
                if (radioButtons.length > 0) {

                    radioButtons.forEach(function (radioButton) {
                        if (radioButton.checked) {
                            checkedRadioButton = radioButton.getAttribute('id');
                        }
                    });
                }


                fetching_availabilities(checkedRadioButton);

            }



        }
    });

    function getGroupValue(x, value) {
        var group = value.match(/\d+/g);
        if (group.length === 1) {
            if (x === parseInt(group[0])) {
                return true;
            }
        } else if (group.length === 2) {
            if (x >= parseInt(group[0]) && x <= parseInt(group[1])) {
                return true;
            }
        }
    }

    function updateLabels() {
        const labels = document.querySelectorAll("#availability label.availableRadiolabel span.time")
        const width = window.innerWidth

        labels.forEach(span => {
          const originalText = span.textContent.trim().replace(/\s*-\s*/g, " - ")

          if (width >= 992 && width <= 1280) {
            span.innerHTML = originalText.replace(" - ", " -<br>")
          } else {
            span.innerHTML = originalText
          }
        })
    }

    window.addEventListener("load", updateLabels)
    window.addEventListener("resize", updateLabels)
});

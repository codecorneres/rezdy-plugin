jQuery(document).ready(function($) {
    var repeaters = $('.acf-field-repeater');
    repeaters.each(function() {
        var currentRepeater = $(this);
        var dataNameValue = currentRepeater.attr('data-name');
        if(dataNameValue === 'tg_price_options'){
            var rows = currentRepeater.find('.acf-row:not(.acf-clone)');
            var A_FieldGet = false;
            var C_FieldGet = false;
            var E_FieldGet = false;
            rows.each(function(index) {
                var currentRow = $(this);
                var selectField = currentRow.find("[data-placeholder='Select']");
                selectField.prop('disabled', 'disabled');
                var selectValue = selectField.val();
                    if(selectValue === 'ADULT'){
                        A_FieldGet = true;
                    }
                    if(selectValue === 'CHILD'){
                        C_FieldGet = true;
                    }
                    if(selectValue === 'UNIQUE_PRICE'){
                        E_FieldGet = true;
                    }     
            });

            currentRepeater.find(".acf-repeater-add-row").on('click', function() {
                setTimeout(function () {
                    var countrows = currentRepeater.find('.acf-row:not(.acf-clone)');
                    var lastRow = countrows.last();
                    var lastRowsSelectField = lastRow.find("[data-placeholder='Select']");
                    if(A_FieldGet){
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="ADULT"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('selected', false);
                        lastRowsSelectField.find('option[value="ADULT"]').prop('selected', false); 
                    }
                    if(C_FieldGet){
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="CHILD"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('selected', false);
                        lastRowsSelectField.find('option[value="CHILD"]').prop('selected', false);
                    }
                    if(E_FieldGet){
                        lastRowsSelectField.find('option[value="ADULT"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="CHILD"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('disabled', 'disabled');
                        lastRowsSelectField.find('option[value="UNIQUE_PRICE"]').prop('selected', false);
                    }  
                }, 200);
                
            });

        }
        if (dataNameValue === 'tg_availability') {
            currentRepeater.find('.hasDatepicker').prop('disabled', 'disabled');
            
            // var date_time_picker = currentRepeater.find('.acf-date-time-picker');
            // date_time_picker = date_time_picker.find('input[type="hidden"]')
            // console.log(date_time_picker);
        }

    });

});
// form for accordian type details data

$(document).ready(function () {
    $('.toggle').each(function () {
        $(this).on('click', function () {
            $(this).parents('.Billing_Contact').toggleClass("intro");
            $(this).siblings('.content').toggle();
        });
    });



    // var requestData = {
    //     "ItemQuantity[P0ZJNC][0][priceOption][id]": "985171",
    //     "ItemQuantity[985171][0][quantity]": "1",
    //     "ItemQuantity[P0ZJNC][1][priceOption][id]": "985564",
    //     "ItemQuantity[985564][1][quantity]": "4",
    //     "order[2][product_code]": "P0ZJNC",
    //     "order[2][sessionDate]": "30 Mar 2024 09:00",
    //     "participant[2][0][first_name]": "",
    //     "participant[2][0][last_name]": "",
    //     "participant[2][1][first_name]": "",
    //     "participant[2][1][last_name]": "",
    //     "participant[2][2][first_name]": "",
    //     "participant[2][2][last_name]": "",
    //     "participant[2][3][first_name]": "",
    //     "participant[2][3][last_name]": "",
    //     "participant[2][4][first_name]": "",
    //     "participant[2][4][last_name]": "",
    //     "fname": "asd",
    //     "lname": "asd",
    //     "phone": "23313133333",
    //     "email": "test@gmail.com",
    //     "country": "in",
    //     "comments": "",
    //     "radio": "on",
    //     "priceOptions[2][0][optionLabel]": "Adult",
    //     "priceOptions[2][0][price]": "59.00",
    //     "priceOptions[2][0][name]": "GYG CDT Florence Tipsy Tour",
    //     "priceOptions[2][0][value]": "1",
    //     "priceOptions[2][1][optionLabel]": "Child",
    //     "priceOptions[2][1][price]": "200.00",
    //     "priceOptions[2][1][name]": "GYG CDT Florence Tipsy Tour",
    //     "priceOptions[2][1][value]": "4",
    //     "stripeToken": "tok_1OyrdzI5qNxBX6fKz6G6t9vq",
    //     "action": "booking_checkout",
    //     "priceValue": "859.00",
    //     "method": "CREDITCARD"
    // };
    // //console.log(requestData);


    // function convertDataToJSON(data) {
    //     let result = {};

    //     // Iterate over the keys in the data object
    //     for (let key in data) {
    //         // Split the key based on '[' and ']'
    //         let parts = key.split(/\[|\]/).filter(Boolean);

    //         // Initialize the current object to the result object
    //         let currentObj = result;

    //         // Iterate over the parts of the key
    //         for (let i = 0; i < parts.length; i++) {
    //             let part = parts[i];

    //             // Check if it's the last part
    //             if (i === parts.length - 1) {
    //                 // Set the value in the current object
    //                 currentObj[part] = data[key];
    //             } else {
    //                 // Check if the next part is a number, initialize an array if necessary
    //                 let nextPart = parts[i + 1];
    //                 let isArray = /^\d+$/.test(nextPart);
    //                 if (!currentObj[part]) {
    //                     currentObj[part] = isArray ? [] : {};
    //                 }

    //                 // Move to the next object
    //                 currentObj = currentObj[part];
    //             }
    //         }
    //     }

    //     return result;
    // }

    // let convertData = convertDataToJSON(requestData);

    // // Output the result
    // //console.log(convertData);



    // let itemParamsLayer = {};
    // let outCounter = 0;
    // let products = {};
    // var inserted = false;
    // itemParamsLayer['event'] = 'purchase';
    // itemParamsLayer['ecommerce'] = {};
    // itemParamsLayer['ecommerce']['currency'] = 'EUR';
    // itemParamsLayer['ecommerce']['purchase'] = {};
    // itemParamsLayer['ecommerce']['purchase']['actionField'] = {};
    // itemParamsLayer['ecommerce']['purchase']['actionField']['id'] = "T_12345_1";
    // itemParamsLayer['ecommerce']['purchase']['actionField']['revenue'] = "149";

    // itemParamsLayer['ecommerce']['purchase']['products'] = [];

    // for (let key in convertData.order) {
    //     let order = convertData.order[key];

    //     if (order['product_code'] && order['sessionDate']) {

    //         let inCounter = 0;
    //         let valueTotl = 0;
    //         let priceTotl = 0;

    //         for (let i in convertData.priceOptions[key]) {
    //             let option = convertData.priceOptions[key][i];
    //             console.log(option);
    //             //products[key] = {};
    //             products[outCounter] = {};
    //             products[outCounter]['id'] = order['product_code'];
    //             products[outCounter]['name'] = option.name;
    //             var vall = parseInt(option.price) * parseInt(option.value);
    //             priceTotl = priceTotl + vall;
    //             valueTotl = valueTotl + parseInt(option.value);
    //             //var priceTotl = parseInt(option.price);
    //             inCounter++;

    //         }
    //         products[outCounter]['price'] = priceTotl;
    //         products[outCounter]['quantity'] = valueTotl;
    //         console.log(products);
    //         console.log(itemParamsLayer);





    //     }

    //     outCounter++;
    // }

    // itemParamsLayer['ecommerce']['purchase']['products'].push(products);
    // let productsArray = Object.values(itemParamsLayer['ecommerce']['purchase']['products'][0]);
    // //console.log(productsArray);
    // itemParamsLayer['ecommerce']['purchase']['products'] = productsArray;
    // console.log(itemParamsLayer);


    // window.onload = function() {
    //     // Construct the purchase event data
    //     const purchaseEventData = {
    //         event: "purchase", // Event type
    //         ecommerce: {
    //             currency: "USD", // Set your currency here
    //             purchase: {
    //                 actionField: {
    //                     id: "T_12345_1", // Replace with your transaction ID
    //                     revenue: 149, // Replace with your transaction total
    //                     // Add any other relevant fields like tax, shipping, etc.
    //                 },
    //                 products: [{
    //                     id: "489021", // Product SKU
    //                     name: "Performance Max Mastery", // Product name
    //                     price: 149, // Product price
    //                     quantity: 1 // Product quantity
    //                 }]
    //             }
    //         }
    //     };

    //     // Push the purchase event data to the dataLayer
    //     window.dataLayer = window.dataLayer || [];
    //     window.dataLayer.push(purchaseEventData);
    // };

    // const transactionId = 'ABC123';
    // const transactionTotal = 149;
    // const transactionProducts = [{
    //     sku: "489021",
    //     name: "Performance Max Mastery", 
    //     price: 149, 
    //     quantity: 1
    // }];
    // const eventId = 14;
    // const startTime = Date.now(); // Get current timestamp

    // // Construct the dynamic data object
    // const eventData = {
    //     transactionId: transactionId,
    //     transactionTotal: transactionTotal,
    //     transactionProducts: transactionProducts,
    //     event: "gtm.load",
    //     gtm: { uniqueEventId: eventId, start: startTime, priorityId: 5 }, 
    //     url_passthrough: false, 
    //     developer_id: { dMWZhNz: true },
    //     ads_data_redaction: false
    // };

    // // Push the dynamic data to dataLayer
    // dataLayer.push(eventData);
    // console.log(dataLayer);
});




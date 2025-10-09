document.addEventListener('DOMContentLoaded', function () {
    const transactionId = googleTagsData.transactionId;
    var itemsdata = googleTagsData.itemsdata;
    var totalPaid = googleTagsData.totalPaid;
    var fname = googleTagsData.fname;
    var lname = googleTagsData.lname;
    var phone = googleTagsData.phone;
    var email = googleTagsData.email;
    var country = googleTagsData.country;

    jsonItemdata = JSON.parse(itemsdata);
    let items = [];
    for (let key in jsonItemdata) {
        const product = jsonItemdata[key];
        if (product.productCode) {
            for (let i in product.quantities) {
                let item = {
                    item_id: product.productCode,
                    item_name: product.productName,
                    item_variant: product.startTimeLocal,
                    price: product.quantities[i].optionPrice,
                    quantity: product.quantities[i].value,
                };
                items.push(item);
            }

        }
    }


    let purchaseEventData = {};
    purchaseEventData = {
        event: "purchase",
        ecommerce: {
            transaction_id: transactionId,
            // Sum of (price * quantity) for all items.
            value: parseInt(totalPaid, 10),
            tax: 0,
            shipping: 0,
            currency: "EUR",
            items: items,
        },
        fname: fname,
        lname: lname,
        phone: phone,
        email: email,
        country: country,
    };

    console.log(purchaseEventData);

    // Push the purchase event data to the dataLayer
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push(purchaseEventData);


});
jQuery(document).ready(function($) {

    function showLoader() {
        var loader = $('<div id="my-loader">Loading...</div>');
        $('body').append(loader);
    }
    
    function hideLoader() {
        $('div#my-loader ').remove();
    }


    //console.log(ajax_object.ajax_url);
    // $(document).on('click', '.synch-to-rezdy', function () {
    //     showLoader();
    //     var data = {
    //         action      : 'synchronization'
    //     };
    //     var formData = new FormData();
    //     for (var key in data) {
    //         formData.append(key, data[key]);
    //     }
    //     var response = fetch(ajax_object.ajax_url, {
    //         method: 'POST',
    //         body: formData
    //     })
    //     .then(function(response) {
    //         return response.json();
    //     })
    //     .then(function(data) {
    //         hideLoader();
    //         console.log(data);
    //     })
    //     .catch(function(error) {
    //         console.log(error)
    //     });
    // });     
});

<?php

namespace CC_RezdyAPI\Page;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionBatchUpdate;
use CC_RezdyAPI\Rezdy\Util\Config;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as GuzzleClient;

class Page
{
    private $pageContext;

    public function __construct(App $pageContext)
    {
        $this->pageContext = $pageContext;
        $this->setupActions();
        
        return $this;
    }

    protected function setupActions()
    {
        add_action('acf/input/admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('save_post', [$this, 'handlePostUpdate'], 20, 3);
        add_action('acf/input/admin_head', [$this, 'my_acf_admin_head']);


        add_action('wp_ajax_delete_availability_row', [$this, 'delete_availability_row_callback']);
        add_action('wp_ajax_delete_availability_row', [$this, 'delete_availability_row_callback']);

    }

    

    public function enqueue_scripts() {
        
        $base = plugin_basename($this->pageContext->getPluginFile());
        $baseArray = explode('/', $base );
        $base = $baseArray[0]; 
        $base = WP_PLUGIN_URL . '/' . $base; 
        wp_enqueue_script('admin-datetimepicker-availability-js', $base . '/src/assets/includes/js/admin-datetimepicker-availability.js', [], '1.0', true);
    }

    function delete_availability_row_callback(){
        $data_id = $_POST['data_id'];
        $parts = explode('-', $data_id);
        $rowIndex = $parts[1];
        $post_id = $_POST['post_id'];
        $tg_availability     = get_post_meta($post_id, 'tg_availability', true);
        $status = '';
        for ($i = 0; $i <  $tg_availability; $i++) {
            if($rowIndex == $i){
                $session_date_local = get_post_meta($post_id, "tg_availability_{$rowIndex}_session_date_local", true);
                $session_date_local = date("Y-m-d", strtotime($session_date_local));
                $start_time_local = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$rowIndex}_start_time_local", true);
                $end_time_local = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$rowIndex}_end_time_local", true);
                $rezdy_product_code = get_post_meta($post_id, 'rezdy_product_code', true);

                $guzzleClient = new RezdyAPI($this->pageContext::API_KEY);
                $availability_search     = new SessionSearch([
                    'productCode'       => $rezdy_product_code,
                    'startTimeLocal'       => $start_time_local,
                    'endTimeLocal'       => $end_time_local
                ]);
                $availability = $guzzleClient->availability->search($availability_search);
                $result         = json_decode($availability, true);
                $seats          = $result['sessions'][0]['seats'];
                $seatsAvailable = $result['sessions'][0]['seatsAvailable'];
                $startTimeLocal = $result['sessions'][0]['startTimeLocal'];
                if(trim($seats) != trim($seatsAvailable)){
                    $status = false;
                }else{
                    $status = true;
                }
                break; 
            }
        }


        wp_send_json(array ('rowIndex' => $rowIndex, 'post_id' => $post_id, 'tg_availability' => $tg_availability, 'status' => $status, 'seats' => $seats, 'seatsAvailable' => $seatsAvailable, 'startTimeLocal' => $startTimeLocal ) );
    }

    function my_acf_admin_head() {
        
        ?>
        <style>
            /* Style for the custom loader */
            div#my-loader {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }    
        </style>
        <script type="text/javascript">
        (function($) {


            function showLoader() {
                var loader = $('<div id="my-loader">Loading...</div>');
                $('body').append(loader);
            }

            function hideLoader() {
                $('div#my-loader ').remove();
            }

            var ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
            acf.add_action('remove', function( $el ){
                console.log($el);
                if($el.find(".hasDatepicker").val()){
                    var data_id = $el.attr('data-id');
                    console.log(data_id);
                    var urlParams = new URLSearchParams(window.location.search);
                    var post_id = urlParams.get('post');
                    console.log(post_id);
                    showLoader();
                    var data = {
                        action      : 'delete_availability_row',
                        data_id     : data_id,
                        post_id     : post_id
                    };
                    var formData = new FormData();
                    for (var key in data) {
                        formData.append(key, data[key]);
                    }
                    var response = fetch(ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        hideLoader();
                        console.log(data);
                        console.log(data.status);
                        if(data.status === false){
                            alert("(" + data.startTimeLocal + ") This session have some orders so it can not be delete.");
                            location.reload();
                        }
                    })
                    .catch(function(error) {
                        console.log(error)
                    });
                }
                
            });



            
        })(jQuery);
        </script>
            
        <?php	
        
    }

    public function handlePostUpdate($post_id, $post, $update)
    {
        //wp_die('Custom error message. Post not updated.');
        App::custom_logs('ActualUpdate:');
        // Check for autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }  

        // Define allowed post types
        $allowed_post_types = $this->pageContext::ALLOWED_POST_TYPE;

        if (in_array($post->post_type, $allowed_post_types)) {
            
            // Retrieve all post meta values at once
            
            $post_data = get_post($post_id);
            $meta_keys = ['tour_price', 'rezdy_product_code', 'tg_tour_hour', 'tg_tour_max', 'tg_description', 'section_content'];
            $post_meta = array_map(
                function ($key) use ($post_id) {
                    return get_post_meta($post_id, $key, true);
                },
                $meta_keys
            );
            $description            = get_post_meta($post_id, 'tg_description', true);
            $tour_max               = get_post_meta($post_id, 'tg_tour_max', true);
            $tour_hour              = str_replace(" hrs", "", get_post_meta($post_id, 'tg_tour_hour', true));
            $tour_price             = preg_replace("/[^0-9.]/", "",  get_post_meta($post_id, 'tour_price', true));
            $shortDescription       = get_post_meta($post_id, 'tg_tour_flexible_travel', true);
            $rezdy_product_code     = get_post_meta($post_id, 'rezdy_product_code', true);
            $post_title             = $post_data->post_title;
            $tour_type              = get_post_meta($post_id, 'tg_tour_type', true);

            $guzzleClient = new RezdyAPI($this->pageContext::API_KEY);
            $product_get = $guzzleClient->products->get($rezdy_product_code);
            App::custom_logs("update: " . $update);
            if ($update) {
                
                //wp_die(json_encode($product_get->product));
                if(!empty($product_get->product) && isset($product_get->product)){
                    App::custom_logs('Product Update');
                    //wp_die(json_encode($product_get->product));
                    $product_update_params = [
                        'name'                          => $post_title,
                        'description'                   => $description,
                        'shortDescription'              => $shortDescription,
                        'productType'                   => $tour_type,
                        'durationMinutes'               => $tour_hour * 60,
                    ];
                    $this->product_update($guzzleClient, $rezdy_product_code, $product_update_params, $post_id);

                    sleep(2);

                    $tg_availability     = get_post_meta($post_id, 'tg_availability', true);
                    App::custom_logs("tg_availability: " . $tg_availability);
                
                    $old_tg_availability = get_post_meta($post_id, 'old_tg_availability', true);
                    App::custom_logs("old_tg_availability: " . $old_tg_availability);

                    $removedRowFound = false;
                    ## For Remove Rows   
                    if($old_tg_availability > $tg_availability){
                        App::custom_logs('Index1233');
                        for ($i = 0; $i <  $old_tg_availability; $i++) {
                            App::custom_logs('Index: ' . $i);

                            $oldStartTime = get_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", true);

                            $tgStartTime = get_post_meta($post_id, "tg_availability_{$i}_session_date_local", true);



                            if( ($oldStartTime) && ( empty($tgStartTime) || !isset($tgStartTime) )){

                                App::custom_logs('only old have start time');
                                $sessionId = get_post_meta($post_id, "old_tg_availability_{$i}_session_id", true);
                                if($sessionId){
                                    if($removedRowFound){

                                    }else{
                                        App::custom_logs("Third ELSE Session ID For Delete: " . $sessionId);
                                        $result = $this->availability_delete_request($sessionId);
                                        App::custom_logs("Third ELSE delete result: " . $result);

                                        update_post_meta($post_id, "old_tg_availability", $i);

                                    }

                                    delete_post_meta($post_id, "tg_availability_{$i}_session_id");

                                    delete_post_meta($post_id, "old_tg_availability_{$i}_session_id");
                                    delete_post_meta($post_id, "old_tg_availability_{$i}_session_date_local");
                                    delete_post_meta($post_id, "old_tg_availability_{$i}_start_time_local");
                                    delete_post_meta($post_id, "old_tg_availability_{$i}_end_time_local");
                                    delete_post_meta($post_id, "old_tg_availability_{$i}_all_day");
                                    delete_post_meta($post_id, "old_tg_availability_{$i}_seats");
                                }
                                
                            }
                            else
                            {
                                $oldStartTime = strtotime(get_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", true));

                                $tgStartTime = strtotime(get_post_meta($post_id, "tg_availability_{$i}_session_date_local", true));
                                
                                if($tgStartTime != $oldStartTime){
                                    App::custom_logs('starttime not matching');
                                    if($removedRowFound == false)
                                    {
                                        $removedRowFound = true;
                                        App::custom_logs('second come here!!');
                                        $sessionId = get_post_meta($post_id, "old_tg_availability_{$i}_session_id", true);
                                        App::custom_logs("Session ID For Delete: " . $sessionId);
                                        if($sessionId){
                                            $result = $this->availability_delete_request($sessionId);
                                            App::custom_logs('sub second delete result: ' . $result );

                                            $oldtg = $i;
                                            $oldtg = $oldtg + 1;
                                            $session_id = get_post_meta($post_id, "old_tg_availability_{$oldtg}_session_id", true);
                                            $session_date_local = get_post_meta($post_id, "old_tg_availability_{$oldtg}_session_date_local", true);
                                            $startTimeLocal = get_post_meta($post_id, "old_tg_availability_{$oldtg}_start_time_local", true);
                                            $endTimeLocal = get_post_meta($post_id, "old_tg_availability_{$oldtg}_end_time_local", true);
                                            $allDay = get_post_meta($post_id, "old_tg_availability_{$oldtg}_all_day", true);
                                            $seats = get_post_meta($post_id, "old_tg_availability_{$oldtg}_seats", true);


                                            $tgCount = $i;
                                            $tgCount = $tgCount + 1;

                                            update_post_meta($post_id, "tg_availability_{$i}_session_id", $session_id);
                                            update_post_meta($post_id, "tg_availability_{$i}_session_date_local", $session_date_local);
                                            update_post_meta($post_id, "tg_availability_{$i}_start_time_local", $startTimeLocal);
                                            update_post_meta($post_id, "tg_availability_{$i}_end_time_local", $endTimeLocal);
                                            update_post_meta($post_id, "tg_availability_{$i}_all_day", $allDay);
                                            update_post_meta($post_id, "tg_availability_{$i}_seats", $seats);
                                            update_post_meta($post_id, "tg_availability", $tgCount);

                                            update_post_meta($post_id, "old_tg_availability_{$i}_session_id", $session_id);
                                            update_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", $session_date_local);
                                            update_post_meta($post_id, "old_tg_availability_{$i}_start_time_local", $startTimeLocal);
                                            update_post_meta($post_id, "old_tg_availability_{$i}_end_time_local", $endTimeLocal);
                                            update_post_meta($post_id, "old_tg_availability_{$i}_all_day", $allDay);
                                            update_post_meta($post_id, "old_tg_availability_{$i}_seats", $seats);
                                            update_post_meta($post_id, "old_tg_availability", $tgCount);
                                        }
                                    }
                                    else
                                    {
                                        App::custom_logs('unwanted condition');
                                        $oldtg = $i;
                                        $oldtg = $oldtg + 1;
                                        $session_id = get_post_meta($post_id, "old_tg_availability_{$oldtg}_session_id", true);
                                        $session_date_local = get_post_meta($post_id, "old_tg_availability_{$oldtg}_session_date_local", true);
                                        $startTimeLocal = get_post_meta($post_id, "old_tg_availability_{$oldtg}_start_time_local", true);
                                        $endTimeLocal = get_post_meta($post_id, "old_tg_availability_{$oldtg}_end_time_local", true);
                                        $allDay = get_post_meta($post_id, "old_tg_availability_{$oldtg}_all_day", true);
                                        $seats = get_post_meta($post_id, "old_tg_availability_{$oldtg}_seats", true);


                                        $tgCount = $i;
                                        $tgCount = $tgCount + 1;

                                        update_post_meta($post_id, "tg_availability_{$i}_session_id", $session_id);
                                        update_post_meta($post_id, "tg_availability_{$i}_session_date_local", $session_date_local);
                                        update_post_meta($post_id, "tg_availability_{$i}_start_time_local", $startTimeLocal);
                                        update_post_meta($post_id, "tg_availability_{$i}_end_time_local", $endTimeLocal);
                                        update_post_meta($post_id, "tg_availability_{$i}_all_day", $allDay);
                                        update_post_meta($post_id, "tg_availability_{$i}_seats", $seats);
                                        update_post_meta($post_id, "tg_availability", $tgCount);

                                        update_post_meta($post_id, "old_tg_availability_{$i}_session_id", $session_id);
                                        update_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", $session_date_local);
                                        update_post_meta($post_id, "old_tg_availability_{$i}_start_time_local", $startTimeLocal);
                                        update_post_meta($post_id, "old_tg_availability_{$i}_end_time_local", $endTimeLocal);
                                        update_post_meta($post_id, "old_tg_availability_{$i}_all_day", $allDay);
                                        update_post_meta($post_id, "old_tg_availability_{$i}_seats", $seats);
                                        update_post_meta($post_id, "old_tg_availability", $tgCount);
                                    }
                                }
                                else{
                                        App::custom_logs('first come here!!');
                                }
                            }
                        }
                    }


                    ## For Update or create rows 
                    if(get_post_meta($post_id, 'tg_availability', true)){
                        for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {


                            if( get_post_meta($post_id, "tg_availability_{$i}_session_date_local", true) )
                            {
                                $session_date_local = get_post_meta($post_id, "tg_availability_{$i}_session_date_local", true);
                                $session_date_local = date("Y-m-d", strtotime($session_date_local));    
                                $startTimeLocal = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true);
                                $endTimeLocal = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true);
                                
                                
                                if(get_post_meta($post_id, "tg_availability_{$i}_all_day", true)){
                                    $startTimeLocal = $session_date_local . " " . '00:00:00';
                                    $endTime = strtotime($session_date_local . " " . '00:00:00');
                                    $endTimeLocal = date('Y-m-d H:i:s', strtotime('+1 day', $endTime));
                                }
                                
                                // if(get_post_meta($post_id, "tg_price_options_{$i}_label", true)){
                                //     $labelArray = get_field("tg_price_options_{$i}_label", $post_id);

                                // }
                                
                                $sessionPriceOptions = $this->priceOptions($post_id);

                                $sessionParams = [
                                    'productCode'                   => $rezdy_product_code,
                                    'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                                    'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true) ? true : false,
                                    'startTimeLocal'                => $startTimeLocal,
                                    'endTimeLocal'                  => $endTimeLocal,
                                    'priceOptions'                  => $sessionPriceOptions
                                ];
                                App::custom_logs("sessionParams: " . json_encode($sessionParams));
                                
                                if(get_post_meta($post_id, "tg_availability_{$i}_session_id", true)){
                                    $sessionId = get_post_meta($post_id, "tg_availability_{$i}_session_id", true);
                                    $availabilitySearch     = new SessionSearch([
                                        'productCode'       => $rezdy_product_code,
                                        'startTimeLocal'       => $startTimeLocal,
                                        'endTimeLocal'       => $endTimeLocal
                                    ]);
                                    $availability = $guzzleClient->availability->search($availabilitySearch);
                                    $result = json_decode($availability, true);
                                    ##======Search availability=====##
                                    if( !empty($result['sessions']) ){

                                        App::custom_logs('have session!!');
                                        $this->availability_update_custom($sessionParams, $post_id, $i);
                                    }

                                }
                                else{
                                    App::custom_logs('not have session id');
                                    $this->availability_create_custom($sessionParams, $post_id, $i);
                                }
                            }else{
                                App::custom_logs('have only sessionid');
                                $sessionId = get_post_meta($post_id, "tg_availability_{$i}_session_id", true);
                                if($sessionId){
                                    $result = $this->availability_delete_request($sessionId);
                                    $resultArray = json_decode($result, true);
                                    if($resultArray['requestStatus']['success'] === false){
                                        wp_die(json_encode($resultArray['requestStatus']['error']['errorMessage']));
                                    }else{
                                        delete_post_meta($post_id, "tg_availability_{$i}_session_id");
                                    }
                                    
                                }
                            }

                        }
                        
                    }

                }else{
                    
                    //wp_die($shortDescription);
                    if( $description && $tour_hour && $tour_type )
                    {  
                        $productParams = [
                            'description'                    => $description,
                            'durationMinutes'                => $tour_hour * 60,
                            'name'                           => $post_title,
                            'productType'                    => $tour_type,
                            'shortDescription'               => $shortDescription,
                        ];

                        $this->product_create($guzzleClient, $post_id, $productParams);
                        sleep(2);
                        $rezdy_product_code = get_post_meta($post_id, 'rezdy_product_code', true);
                        App::custom_logs("tgavail: " . get_post_meta($post_id, 'tg_availability', true));
                        for ($i = 0; $i <  get_post_meta($post_id, 'tg_availability', true); $i++) {
                            App::custom_logs("Index: " . $i);
                            $session_date_local = get_post_meta($post_id, "tg_availability_{$i}_session_date_local", true);
                            $session_date_local = date("Y-m-d", strtotime($session_date_local));    
                            $startTimeLocal = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$i}_start_time_local", true);
                            $endTimeLocal = $session_date_local . " " . get_post_meta($post_id, "tg_availability_{$i}_end_time_local", true);
                            
                            
                            if(get_post_meta($post_id, "tg_availability_{$i}_all_day", true)){
                                $startTimeLocal = $session_date_local . " " . '00:00:00';
                                $endTime = strtotime($session_date_local . " " . '00:00:00');
                                $endTimeLocal = date('Y-m-d H:i:s', strtotime('+1 day', $endTime));
                            }

                            $sessionPriceOptions = $this->priceOptions($post_id);

                            $sessionParams = [
                                'productCode'                   => $rezdy_product_code,
                                'seats'                         => get_post_meta($post_id, "tg_availability_{$i}_seats", true),
                                'allDay'                        => get_post_meta($post_id, "tg_availability_{$i}_all_day", true) ? true : false,
                                'startTimeLocal'                => $startTimeLocal,
                                'endTimeLocal'                  => $endTimeLocal,
                                'priceOptions'                  => $sessionPriceOptions
                            ];
                            App::custom_logs(json_encode($sessionParams));
                            $this->availability_create_custom($sessionParams, $post_id, $i);
                        }
                    }
                }

            }
        }
        return $post_id;  
    }

    public function product_create($guzzleClient, $post_id, $productParams)
    {
        $product = new Product($productParams);

        $sessionPriceOptions = $this->priceOptions($post_id);
        $sessionPriceOptionParams = [];
        foreach ($sessionPriceOptions as $params) {
            $sessionPriceOptionParams[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptionParams as $sessionPriceOption) {
            $product->attach($sessionPriceOption);
        }

        $rezdy_res = $guzzleClient->products->create($product);

        if ($rezdy_res->hadError ==  true) {
            App::sendMail('responseupdate' . json_encode($rezdy_res));

            wp_die(implode(',', $rezdy_res->error));
        }
        update_post_meta($post_id, 'rezdy_product_code', $rezdy_res->product->productCode);
    }

    public function product_update($guzzleClient, $rezdy_product_code, $productParams, $post_id)
    {
        
        $productUpdate = new ProductUpdate($productParams);
        

        $sessionPriceOptions = $this->priceOptions($post_id);

        $sessionPriceOptionParams = [];
        foreach ($sessionPriceOptions as $params) {
            $sessionPriceOptionParams[] = new PriceOption($params);
        }

        foreach ($sessionPriceOptionParams as $sessionPriceOption) {
            $productUpdate->attach($sessionPriceOption);
        }
        
        $rezdy_res = $guzzleClient->products->update($rezdy_product_code, $productUpdate);
        if ($rezdy_res->hadError ==  true) {
            wp_die(implode(',', $rezdy_res->error));    
        }
        
    }


    // public function availability_create($guzzleClient, $sessionParams)
    // {
    //     $session = new SessionCreate($sessionParams);
    //     return $guzzleClient->availability->create($session);
    // }
    
    // public function availability_update($guzzleClient, $sessionParams)
    // {
    //     $session = new SessionUpdate($sessionParams);
        
    //     return $guzzleClient->availability->update($session);
    //     ##return $guzzleClient->availability->update_availability_batch($session);
    // }

    public function availability_create_custom($sessionParams, $post_id, $i){
            App::custom_logs("Create: " . json_encode($sessionParams));
            
            //sleep(2);
            $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_create');
            $rezdy_api_key = get_option('cc_rezdy_api_key');
            $apiUrl = $baseUrl;
            $request_type = 'POST';
            $resultArray  = $this->availability_requests($apiUrl, $request_type, $sessionParams);
            App::custom_logs(json_encode($resultArray));
            if ( $resultArray['requestStatus']['success'] == false ) {
                wp_die($resultArray['requestStatus']['error']['errorMessage']);
            }else{
                App::custom_logs("CreateResponse: " . json_encode($resultArray));
                $session_id = $resultArray['session']['id'];
                $startTimeLocal = $resultArray['session']['startTimeLocal'];
                $endTimeLocal = $resultArray['session']['endTimeLocal'];
                $allDay = $resultArray['session']['allDay'] ? true : false;
                $seats = $resultArray['session']['seats'];

                $start_timestamp = strtotime($startTimeLocal);
                $end_timestamp = strtotime($endTimeLocal);

                $session_date_local = date("Y-m-d", $start_timestamp);
                $startTimeLocal = date("H:i:s", $start_timestamp);

                $endTimeLocal = date("H:i:s", $end_timestamp);

                update_post_meta($post_id, "tg_availability_{$i}_session_id", $session_id);
                update_post_meta($post_id, "tg_availability_{$i}_session_date_local", $session_date_local);
                update_post_meta($post_id, "tg_availability_{$i}_start_time_local", $startTimeLocal);
                update_post_meta($post_id, "tg_availability_{$i}_end_time_local", $endTimeLocal);
                update_post_meta($post_id, "tg_availability_{$i}_all_day", $allDay);
                update_post_meta($post_id, "tg_availability_{$i}_seats", $seats);


                update_post_meta($post_id, "old_tg_availability_{$i}_session_id", $session_id);
                update_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", $session_date_local);
                update_post_meta($post_id, "old_tg_availability_{$i}_start_time_local", $startTimeLocal);
                update_post_meta($post_id, "old_tg_availability_{$i}_end_time_local", $endTimeLocal);
                update_post_meta($post_id, "old_tg_availability_{$i}_all_day", $allDay);
                update_post_meta($post_id, "old_tg_availability_{$i}_seats", $seats);
                update_post_meta($post_id, "old_tg_availability", $i + 1);
            }
            return;    
    }


    public function availability_update_custom($sessionParams,$post_id, $i){
            App::custom_logs("Update: " . json_encode($sessionParams));
            //wp_die(json_encode($updatesessionParams));
            
            sleep(2);
            $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_update') . "product/" . $sessionParams['productCode'] . "/startTimeLocal/" . $sessionParams['startTimeLocal'];
            $apiUrl = $baseUrl;
            $request_type = 'PUT';
            $resultArray  = $this->availability_requests($apiUrl, $request_type, $sessionParams);
            if ( $resultArray['requestStatus']['success'] == false ) {
                //wp_die(json_encode($resultArray));
                wp_die($resultArray['requestStatus']['error']['errorMessage']);
            }else{
                App::custom_logs("UpdateResponse: " . json_encode($resultArray));
                $session_id = $resultArray['session']['id'];
                $startTimeLocal = $resultArray['session']['startTimeLocal'];
                $endTimeLocal = $resultArray['session']['endTimeLocal'];
                $allDay = $resultArray['session']['allDay'];
                $seats = $resultArray['session']['seats'];

                $start_timestamp = strtotime($startTimeLocal);
                $end_timestamp = strtotime($endTimeLocal);

                $session_date_local = date("Y-m-d", $start_timestamp);
                $startTimeLocal = date("H:i:s", $start_timestamp);

                $endTimeLocal = date("H:i:s", $end_timestamp);


                update_post_meta($post_id, "tg_availability_{$i}_session_id", $session_id);
                update_post_meta($post_id, "tg_availability_{$i}_session_date_local", $session_date_local);
                update_post_meta($post_id, "tg_availability_{$i}_start_time_local", $startTimeLocal);
                update_post_meta($post_id, "tg_availability_{$i}_end_time_local", $endTimeLocal);
                update_post_meta($post_id, "tg_availability_{$i}_all_day", $allDay);
                update_post_meta($post_id, "tg_availability_{$i}_seats", $seats);


                update_post_meta($post_id, "old_tg_availability_{$i}_session_id", $session_id);
                update_post_meta($post_id, "old_tg_availability_{$i}_session_date_local", $session_date_local);
                update_post_meta($post_id, "old_tg_availability_{$i}_start_time_local", $startTimeLocal);
                update_post_meta($post_id, "old_tg_availability_{$i}_end_time_local", $endTimeLocal);
                update_post_meta($post_id, "old_tg_availability_{$i}_all_day", $allDay);
                update_post_meta($post_id, "old_tg_availability_{$i}_seats", $seats);
                update_post_meta($post_id, "old_tg_availability", $i + 1);
            }
            return;    
    }
    
    public function availability_delete_request($sessionId){

        App::custom_logs('Arrived in delete request function');
        ##====Custom API for availability Delete====##
        $baseUrl = Config::get('endpoints.base_url') . Config::get('endpoints.availability_delete') . $sessionId;
        $rezdy_api_key = get_option('cc_rezdy_api_key');
        $apiUrl = $baseUrl . "?apiKey=" . $rezdy_api_key;
        $request_type = "DELETE";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        App::custom_logs($result);
        curl_close($ch);
        return $result;
    }

    public function availability_requests($apiUrl, $request_type, $sessionParams){

        $rezdy_api_key = get_option('cc_rezdy_api_key');
        ##====Custom API for availability====##
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$apiUrl");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sessionParams));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Apikey: '.$rezdy_api_key;
        $headers[] = 'Cookie: JSESSIONID=19D1B116214696EA41B2579C7080DD81';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        curl_close($ch);
        $resultArray = json_decode($result, true);
        return $resultArray;
    }

    public function priceOptions($post_id){
        $sessionPriceOptions = [];
        for ($p = 0; $p < get_post_meta($post_id, "tg_price_options", true); $p++) {
            $price = get_post_meta($post_id, "tg_price_options_{$p}_price", true);
            $label = get_field("tg_price_options_{$p}_label", $post_id);
            $label['label']= ($label['label'] == 'Everyone') ? 'Quantity': $label['label'];
            $priceOption = [
                'price' => $price,
                "label" => $label['label']
            ];

            $minQuantity = get_post_meta($post_id, "tg_price_options_{$p}_minQuantity", true);
            $maxQuantity = get_post_meta($post_id, "tg_price_options_{$p}_maxQuantity", true);
            $priceGroupType = get_post_meta($post_id, "tg_price_options_{$p}_priceGroupType", true);

            if(isset($priceGroupType) && !empty($priceGroupType) && $label['value'] == 'GROUP'){
                $priceOption['minQuantity'] = $minQuantity;
                $priceOption['maxQuantity'] = $maxQuantity;
                $priceOption['priceGroupType'] = $priceGroupType;
            }

             $sessionPriceOptions[] = $priceOption;
        }
        

        return $sessionPriceOptions;
    }
}

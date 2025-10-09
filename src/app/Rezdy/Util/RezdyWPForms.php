<?php

namespace CC_RezdyAPI\Rezdy\Util;

use CC_RezdyAPI\Rezdy\Requests\Monday;

class RezdyWPForms
{
    public $mondayRequest;

    public function __construct()
    {
        $this->mondayRequest = new Monday();
        $this->mondayRequest->setBoardId(7741923425);
    }

    public function setup_hooks()
    {
        if (is_plugin_active('wpforms/wpforms.php') || is_plugin_active('wpforms-lite/wpforms.php')) {
            add_action('wpforms_process_complete', [$this, 'send_to_monday_group_leads'], 10, 4);

            add_action( 'wp_enqueue_scripts', [$this, 'add_script_abandoned_cart'] );
            add_action('wp_ajax_rezdy_wp_forms_abandoned', [$this, 'rezdy_wp_forms_abandoned']);
            add_action('wp_ajax_nopriv_rezdy_wp_forms_abandoned', [$this, 'rezdy_wp_forms_abandoned']);
        }
    }

    public function send_to_monday_group_leads($fields, $entry, $form_data, $entry_id)
    {
        $formId = data_get($form_data, 'id');
        $validFormIds = [5990, 7865];

        if (in_array($formId, $validFormIds)) {
            $data = $this->prepare_data($entry);
            $query = 'mutation {
                create_item (
                    board_id: ' . $this->mondayRequest->api_board_id . ',
                    group_id: "topics",
                    item_name: "'. data_get($entry, 'fields.0') .'",
                    column_values: "'. addslashes(json_encode($data)) .'"
                ) {
                    id
                }
            }';

            // Execute the query using the generic method
            $response = $this->mondayRequest->executeQuery($query);

            /*error_log(json_encode([
                '$data' => $data,
                '$fields' => $fields,
                '$entry' => $entry,
                '$form_data' => $form_data,
                '$entry_id' => $entry_id,
                '$response' => $response
            ]));*/
        }
    }

    public function prepare_data($entry)
    {
        $data = [];

        $plugin_theme_type = get_option('cc_picked_color');
        $plugin_company = '';

        switch ($plugin_theme_type) {
            case 'theme-cdt':
                $plugin_company = 'CDT';
                break;
            case 'theme-rwc':
                $plugin_company = 'RWC';
                break;
            case 'theme-jtr':
                $plugin_company = 'JTR';
                break;
            case 'theme-tipsy':
                $plugin_company = 'TT';
                break;
            default:
                break;
        }

        $tour_date = $this->get_tour_date(data_get($entry, 'fields.11'));

        return [
            'name' => data_get($entry, 'fields.0'),
            'lead_status' => 'New Lead',
            'lead_company' => $plugin_company,
            'text' => 'WEB ' . $plugin_company . ' Team Building Inquiry',
            'lead_email' => [
                'email' => data_get($entry, 'fields.1'),
                'text' => data_get($entry, 'fields.1')
            ],
            'lead_phone' => data_get($entry, 'fields.6'),
            'status_1_mkm6bfep' => data_get($entry, 'form_action_type', 'Product Page Form'),
            'date_mkm6t3kj' => $tour_date
        ];

        return $data;
    }

    public function get_tour_date($value)
    {
        $date = str_replace('\/', '/', $value);

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return date('Y-m-d');
        }

        return date('Y-m-d', $timestamp);
    }

    public function add_script_abandoned_cart()
    {
        // Enqueue the script
        wp_enqueue_script(
            'rezdy-forms',
            PLUGIN_URL . 'assets/includes/js/rezdy-forms.js',
            ['jquery'],
            rand(111111, 999999),
            true
        );

        wp_localize_script('rezdy-forms', 'rezdyFormsData', [
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    public function rezdy_wp_forms_abandoned()
    {
        $entry = data_get($_POST, 'wpforms');
        $form_data = [
            'id' => 7865
        ];
        $entry['form_action_type'] = data_get($_POST, 'form_action_type');

        $this->send_to_monday_group_leads([], $entry, $form_data, null);

        /*error_log(json_encode([
            'type' => 'From Abandoned',
            '$_POST' => $_POST
        ]));*/
    }
}
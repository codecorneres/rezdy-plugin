<?php

namespace CC_RezdyAPI\Rezdy\Util;

use CC_RezdyAPI\Rezdy\Requests\Monday;
use DateTime;

class RezdyGravityForms
{
    public $mondayRequest;

    public function __construct()
    {
        $this->mondayRequest = new Monday();
        $this->mondayRequest->setBoardId(7741923425);
    }

    public function setup_hooks()
    {
        if (class_exists('GFForms') || is_plugin_active('gravityforms/gravityforms.php')) {
            add_action('gform_after_submission', [$this, 'send_to_monday_group_leads'], 10, 2);
            add_action( 'wp_enqueue_scripts', [$this, 'add_script_abandoned_cart'] );

            add_action('wp_ajax_rezdy_gravity_forms_abandoned', [$this, 'rezdy_gravity_forms_abandoned']);
            add_action('wp_ajax_nopriv_rezdy_gravity_forms_abandoned', [$this, 'rezdy_gravity_forms_abandoned']);
        }
    }

    public function send_to_monday_group_leads($entry, $form)
    {
        $formId = data_get($form, 'id');
        $validFormIds = [4, 6];

        if (in_array($formId, $validFormIds)) {
            $data = $this->prepareData($entry);
            $query = 'mutation {
                create_item (
                    board_id: ' . $this->mondayRequest->api_board_id . ',
                    group_id: "topics",
                    item_name: "'. data_get($data, 'name') .'",
                    column_values: "'. addslashes(json_encode($data)) .'"
                ) {
                    id
                }
            }';

            // Execute the query using the generic method
            $response = $this->mondayRequest->executeQuery($query);
            /* error_log(json_encode([
                'response' => $response
            ])); */
        }
    }

    public function prepareData($entry)
    {
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

        $tour_date = $this->get_tour_date(data_get($entry, '11'));
        $tour_dates = data_get($entry, '11');
        $tourLabel = data_get($entry, '10');

        return [
            'name' => data_get($entry, '3'),
            'lead_status' => 'New Lead',
            'lead_company' => $plugin_company,
            'text' => 'WEB ' . $plugin_company . ' ' . $tourLabel,
            'lead_email' => [
                'email' => data_get($entry, '5'),
                'text' => data_get($entry, '5')
            ],
            'lead_phone' => data_get($entry, '6'),
            'status_1_mkm6bfep' => data_get($entry, 'form_action_type', 'Product Page Form'),
            'text_mknec00y' => $tour_dates,
            'date_mkm6t3kj' => $tour_date,
            'text_mkm68qn1' => data_get($entry, '8')
        ];
    }

    public function get_tour_date($value)
    {
        $value = str_replace('\/', '/', $value);

        if (strpos($value, ' - ') !== false) {
            $value = explode(' - ', $value)[0];
        }

        $formattedDate = DateTime::createFromFormat('m/d/Y', $value);
        if ($formattedDate) {
            return $formattedDate->format('Y-m-d');
        }

        $newFormattedDate = new DateTime($formattedDate);

        return $newFormattedDate->format('Y-m-d');
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

    public function rezdy_gravity_forms_abandoned()
    {
        $form = [
            'id' => 6
        ];
        $this->send_to_monday_group_leads($_POST, $form);
    }
}
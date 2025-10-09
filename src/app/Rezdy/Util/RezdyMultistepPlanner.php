<?php

namespace CC_RezdyAPI\Rezdy\Util;

use CC_RezdyAPI\Rezdy\Requests\Monday;

class RezdyMultistepPlanner
{
    public $mondayRequest;

    public function __construct()
    {
        $this->mondayRequest = new Monday();
        $this->mondayRequest->setBoardId(7741923442);
    }

    public function setup_hooks()
    {
        add_action('wp_ajax_planner_multistep_submit', [$this, 'planner_multistep_submit_function'], 10, 2);
        add_action('wp_ajax_nopriv_planner_multistep_submit', [$this, 'planner_multistep_submit_function'], 10, 2);
    }

    public function planner_multistep_submit_function()
    {
        error_log(json_encode([
            'formData' => $_POST
        ]));

        $data = $this->prepare_data($_POST);
        $itemName = data_get($data, 'name');
        $query = 'mutation {
            create_item (
                board_id: ' . $this->mondayRequest->api_board_id . ',
                group_id: "new_group29179",
                item_name: "'. $itemName .'",
                column_values: "'. addslashes(json_encode($data)) .'"
            ) {
                id
            }
        }';

        // Execute the query using the generic method
        $response = $this->mondayRequest->executeQuery($query);
        wp_send_json([
            'post data' => $_POST,
            'resposne planner' => $response,
            'planner data' => $data
        ]);
        wp_die();
    }

    public function prepare_data($data)
    {
        $tourDates = data_get($data, 'Date');
        $firstDate = $tourDates[0] ?? null;
        $lastDate = $tourDates[count($tourDates) - 1] ?? $firstDate;

        if (count($tourDates) <= 1) {
            $tourDatesValue = [
                'from' => $firstDate,
                'to' => $firstDate,
            ];
        } else {
            $tourDatesValue = [
                'from' => $firstDate,
                'to' => $lastDate,
            ];
        }

        $budgets = [
            '2500+' => '5',
            '2000' => '1',
            '1500' => '0',
            '1000' => '2',
            '800' => '3',
            '600' => '7',
            '400' => '8',
            '200' => '9',
        ];
        $badgetValue = data_get($data, 'budget', '200');
        $budget = $budgets[$badgetValue] ?? '9';

        return [
            'name' => data_get($data, 'firstname') . ' ' . data_get($data, 'lastname'),
            'phone_mknfpjhq' => data_get($data, 'phone'),
            'email_mknfrc0q' => [
                'email' => data_get($data, 'email'),
                'text' => data_get($data, 'email')
            ],
            'date_mknf89as' => date('Y-m-d'), // Date created
            'color_mknf4d6x' => '', // Status
            'text_mknfj2xq' => implode(', ', data_get($data, 'Location')), // Destinations
            'timerange_mknfhbk6' => $tourDatesValue, // Travel Dates
            'color_mknfy7wz' => data_get($data, 'companion'), // Travel with
            'numeric_mknfxf5m' => intval(data_get($data, 'people')), // How many people will be travelling?
            'numeric_mknf4ds6' => intval(data_get($data, 'people18')), // How many group are under 18?
            'text_mknfhggv' => implode(', ', data_get($data, 'activities')), // Travel Categories
            'color_mknf6xtb' => data_get($data, 'mood'), // How Busy
            'color_mkngsz10' => $budget, // Budget // TODO: Fix this issue where it doesn't save
        ];
    }
}
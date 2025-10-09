<?php

namespace CC_RezdyAPI\Rezdy\Util;

use CC_RezdyAPI\Rezdy\Requests\Monday;

class GravityForms
{
    public $mondayRequest;

    public function __construct()
    {
        $this->mondayRequest = new Monday();
        $this->mondayRequest->setBoardId(7741923425);
    }

    public function setup_hooks()
    {
        if (class_exists('GFForms')) {
            add_action('gform_after_submission', [$this, 'send_to_monday_group_leads'], 10, 2);
        }
    }

    public function send_to_monday_group_leads($entry, $form)
    {
        $endpoint_url = 'https://thirdparty.com';
        $body = array(
            'first_name' => rgar( $entry, '1.3' ),
            'last_name' => rgar( $entry, '1.6' ),
            'message' => rgar( $entry, '3' ),
        );
        error_log(json_encode([
            'gravity_forms' => 'gravity_forms',
            'entry' => $entry
        ]));

        /* $query = 'mutation {
            create_item (
                board_id: ' . $this->mondayRequest->api_board_id . ',
                group_id: "topics",
                item_name: "'. $item_name .'",
                column_values: "'. addslashes($column_values) .'"
            ) {
                id
            }
        }';

        // Execute the query using the generic method
        $created_item = $this->mondayRequest->executeQuery($query); */
    }
}
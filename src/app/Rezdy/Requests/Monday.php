<?php

namespace CC_RezdyAPI\Rezdy\Requests;

use DateTime;

class Monday
{
    /** @var string */
    public ?string $api_key;

    /** @var string */
    public string $api_version;

    /** @var string */
    public string $api_url;

    /** @var string */
    public string $api_content_type;

    /** @var string */
    public string $api_board_id;

    public function __construct()
    {
        $this->api_url = 'https://api.monday.com/v2';
        $this->api_version = '2023-10';
        $this->api_content_type = 'application/json';
        $this->api_key = get_option('cc_monday_api_key');
        $this->api_board_id = get_option('cc_monday_board_id');
    }

    public function setBoardId($boardId)
    {
        $this->api_board_id = $boardId;
    }

    /**
     * Executes a cURL request with the provided GraphQL query.
     *
     * @param $query The GraphQL query to execute.
     */
    public function executeQuery(string $query)
    {
        // Prepare cURL request
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $this->api_key,
            'Content-Type: ' . $this->api_content_type,
            'API-version: ' . $this->api_version
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));

        // Execute the request and fetch the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Return the API response
        return json_decode($response, true);
    }

    /**
     * Create an item on Monday.com.
     *
     * @param $data The data from the booking widget.
     */
    public function setupMondayItem($data)
    {
        $mondayItemIds = [];

        foreach ($data['items'] as $item) {
            try {
                $participants = data_get($item, 'participants');
                $first_participant = array_shift($participants);
                $participants = array_values($participants);
                $tour_date_time = new DateTime(data_get($item, 'startTimeLocal'));
                $tour_date = $tour_date_time->format('Y-m-d');
                $tour_name = data_get($item, 'productName');
                $tour_code = data_get($item, 'productCode');
                $date_time = new DateTime();
                $item_name = $this->get_names(data_get($first_participant, 'fields'));

                // Create the parent item
                $column_values = json_encode([
                    'email__1' => [
                        'email' => data_get($data, 'customer.email'),
                        'text' => data_get($data, 'customer.email')
                    ],
                    'text__1' => $tour_name,
                    'phone__1' => [
                        'phone' => data_get($data, 'customer.phone'),
                        'text' => data_get($data, 'customer.phone'),
                    ],
                    'date__1' => $tour_date,
                    'hour__1' => [
                        'hour' => (int) $tour_date_time->format('H'),
                        'minute' => (int) $tour_date_time->format('i')
                    ],
                    'numbers__1' => $this->get_age_counts(
                        data_get($item, 'quantities'),
                        ['Adult']
                    ),
                    'numbers8__1' => $this->get_age_counts(
                        data_get($item, 'quantities'),
                        ['Child', 'Infant']
                    ),
                    // status ids
                    // 0 = ABANDONED CART
                    // 1 = PAID
                    // 2 = FAILED
                    'status' => [
                        'index' => 0
                    ],
                    'numbers5__1' => data_get($data, 'payments.0.amount'),
                    'text0__1' => $tour_code,
                    'date4' => $date_time->format('Y-m-d')
                ]);

                $query = 'mutation {
                    create_item (
                        board_id: ' . $this->api_board_id . ',
                        group_id: "topics",
                        item_name: "'. $item_name .'",
                        column_values: "'. addslashes($column_values) .'"
                    ) {
                        id
                    }
                }';

                // Execute the query using the generic method
                $created_item = $this->executeQuery($query);

                // Get the item id
                $created_item_id = data_get($created_item, 'data.create_item.id');

                if ($created_item_id) {
                    $this->create_sub_items($created_item_id, $participants);
                }

                $mondayItemIds[] = $created_item_id;
            } catch (\Exception $e) {
                error_log(__CLASS__ . '::' . __FUNCTION__ . ' -- ' . $e->getMessage());
                continue;
            }
        }

        return $mondayItemIds;
    }

    /**
     * Get the count of age category.
     *
     * @param $data
     * @param $toGets
     */
    protected function get_age_counts($data, $toGets)
    {
        $count = 0;

        foreach ($toGets as $toGet) {
            foreach ($data as $value) {
                if (data_get($value, 'optionLabel') == $toGet) {
                    $count = $count + (int) data_get($value, 'value');
                }
            }
        }

        if ($count < 1 && $toGets == ['Adult']) {
            foreach ($data as $value) {
                $count = $count + (int) data_get($value, 'value');
            }
        }

        return $count;
    }

    /**
     * Get the full name from participants fields
     *
     * @param $data
     */
    protected function get_names($data)
    {
        $first_name = '';
        $last_name = '';

        foreach ($data as $value) {
            if (data_get($value, 'label') == 'First Name') {
                $first_name = data_get($value, 'value');
            }

            if (data_get($value, 'label') == 'Last Name') {
                $last_name = data_get($value, 'value');
            }
        }

        return $first_name . ' ' . $last_name;
    }

    /**
     * Create sub items.
     *
     * @param $created_item_id
     * @param $participants
     */
    protected function create_sub_items($created_item_id, $participants)
    {
        error_log('$participants -- ' . json_encode($participants));
        $count = 1;
        $sub_items = '';

        foreach ($participants as $participant) {
            $item_name = $this->get_names(data_get($participant, 'fields'));
            $sub_items .= 'create_subitem' . $count .': create_subitem (
                    parent_item_id: "'. $created_item_id .'"
                    item_name: "'. $item_name .'"
                ) {
                    id board {id}
                },';
            $count++;
        }

        $sub_items = rtrim($sub_items, ',');
        $query = 'mutation {
            '. $sub_items .'
        }';

        $this->executeQuery($query);
    }

    /**
     * Update the status of the item in Monday
     *
     * @param $monday_item_id
     * @param $status
     */
    public function update_item_status($monday_item_id, $status)
    {
        $column_values = json_encode([
            'status' => [
                'index' => $status
            ]
        ]);
        $query = 'mutation {
            change_multiple_column_values (
                item_id: '. $monday_item_id .',
                board_id: '. $this->api_board_id .',
                column_values: "'. addslashes($column_values) .'"
            ) {
                id
            }
        }';

        $this->executeQuery($query);
    }

    /**
     * Direct add monday item
     */
    public function direct_create_monday_item($item_name, $column_values, $participants)
    {
        $query = 'mutation {
            create_item (
                board_id: ' . $this->api_board_id . ',
                group_id: "topics",
                item_name: "'. $item_name .'",
                column_values: "'. addslashes($column_values) .'"
            ) {
                id
            }
        }';

        // Execute the query using the generic method
        $created_item = $this->executeQuery($query);

        // Get the item id
        $created_item_id = data_get($created_item, 'data.create_item.id');

        if ($created_item_id) {
            $this->direct_create_sub_items($created_item_id, $participants);
        }

        return $created_item_id;
    }

    /**
     * Directly create sub items
     */
    protected function direct_create_sub_items($created_item_id, $participants)
    {
        $count = 1;
        $sub_items = '';

        foreach ($participants as $participant) {
            $sub_items .= 'create_subitem' . $count .': create_subitem (
                    parent_item_id: "'. $created_item_id .'"
                    item_name: "'. $participant .'"
                ) {
                    id board {id}
                },';
            $count++;
        }

        $sub_items = rtrim($sub_items, ',');
        $query = 'mutation {
            '. $sub_items .'
        }';

        $this->executeQuery($query);
    }
}
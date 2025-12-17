<?php

namespace XpeApp\agenda\events;

class PostEvents {
    public static function postEvents(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events = $wpdb->prefix . 'agenda_events';
        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $params = $request->get_params();

        // Check if the parameters are valid
        $validation_error = validateParams($params, ['title', 'date', 'type_id']);
        if ($validation_error) {
            $response = $validation_error;
        // Check if the event type exists
        } elseif (!entityExists($params['type_id'], $table_events_type)) {
            $response = createErrorResponse('invalid_type_id', 'Invalid type does not exist', 400);
        } else {
            // Insert the event into the database
            $result = $wpdb->insert($table_events, prepareData($params, ['title', 'date', 'start_time', 'end_time', 'location', 'topic', 'type_id']));

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert event', 500);
            } else {
                $response = createSuccessResponse(null, 201);
            }
        }

        return $response;
    }
}

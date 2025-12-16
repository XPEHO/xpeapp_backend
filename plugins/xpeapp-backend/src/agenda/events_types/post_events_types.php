<?php

namespace XpeApp\agenda\events_types;

class post_events_types {
    public static function postEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $params = $request->get_params();

        // Check if the parameters are valid
        $validation_error = validateParams($params, ['label', 'color_code']);
        if ($validation_error) {
            $response = $validation_error;
        // Check if an event type with the same label already exists
        } elseif (entityExists($params['label'], $table_events_type, 'label')) {
            $response = createErrorResponse('already_exists', 'Event type already exists', 409);
        } else {
            // Insert the event type into the database
            $result = $wpdb->insert(
                $table_events_type,
                prepareData($params, ['label', 'color_code'])
            );

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert event type', 500);
            } else {
                $response = createSuccessResponse(null, 201);
            }
        }

        return $response;
    }
}
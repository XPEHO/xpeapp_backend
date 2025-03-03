<?php

include_once __DIR__ . '/events_helper.php';

function apiPostEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $params = $request->get_params();

    $validation_error = validateEventParams($params, ['type_id']);
    if ($validation_error) {
        return $validation_error;
    }

    if (!entityExists($params['type_id'], $table_events_type)) {
        return createErrorResponse('invalid_type_id', 'Invalid type does not exist', 400);
    }

    try {
        $result = $wpdb->insert($table_events, prepareEventData($params));

        if ($result === false) {
            return createErrorResponse('db_insert_error', 'Could not insert event', 500);
        }

        return new WP_REST_Response(null, 201);
    } catch (\Throwable $th) {
        return createErrorResponse('error', 'Error', 500);
    }
}
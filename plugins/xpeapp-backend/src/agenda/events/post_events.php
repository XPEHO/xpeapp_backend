<?php

include_once __DIR__ . '/events_helper.php';

function apiPostEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $params = $request->get_params();

    $response = null;

    $validation_error = validateEventParams($params, ['type_id']);
    if ($validation_error) {
        $response = $validation_error;
    } elseif (!entityExists($params['type_id'], $table_events_type)) {
        $response = createErrorResponse('invalid_type_id', 'Invalid type does not exist', 400);
    } else {
        try {
            $result = $wpdb->insert($table_events, prepareEventData($params));

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert event', 500);
            } else {
                $response = new WP_REST_Response(null, 201);
            }
        } catch (\Throwable $th) {
            $response = createErrorResponse('error', 'Error', 500);
        }
    }

    return $response;
}
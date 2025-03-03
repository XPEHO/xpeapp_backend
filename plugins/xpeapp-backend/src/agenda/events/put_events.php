<?php

include_once __DIR__ . '/events_helper.php';

function apiPutEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $params = $request->get_params();

    $validation_error = validateEventParams($params, ['id']);
    if ($validation_error) {
        return $validation_error;
    }

    if (!entityExists($params['id'], $table_events)) {
        return createErrorResponse('not_found', 'Event not found', 404);
    }

    if (!empty($params['type_id']) && !entityExists($params['type_id'], $table_events_type)) {
        return createErrorResponse('invalid_type_id', 'Invalid type_id does not exist', 400);
    }

    $result = $wpdb->update($table_events, prepareEventData($params), ['id' => intval($params['id'])]);

    if ($result === false) {
        return createErrorResponse('db_update_error', 'Could not update event', 500);
    }

    return new WP_REST_Response(null, 204);
}
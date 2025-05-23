<?php

include_once __DIR__ . '/../../utils.php';

function apiPutEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $id = $request->get_param('id');
    $params = $request->get_params();

    // Check if the parameters are valid
    if (empty($id)) {
        $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
    // Check if the event exists
    } elseif (!entityExists($params['id'], $table_events)) {
        $response = createErrorResponse('not_found', 'Event not found', 404);
    // Check if the event type exists if it is provided
    } elseif (!empty($params['type_id']) && !entityExists($params['type_id'], $table_events_type)) {
        $response = createErrorResponse('invalid_type_id', 'Invalid type_id does not exist', 400);
    } else {
        // Update the event in the database
        $result = $wpdb->update($table_events, prepareData($params, ['date', 'start_time', 'end_time', 'title', 'location', 'topic', 'type_id']), ['id' => intval($params['id'])]);

        if ($result === false) {
            $response = createErrorResponse('db_update_error', 'Could not update event', 500);
        } else {
            $response = createSuccessResponse(null, 204);
        }
    }

    return $response;
}
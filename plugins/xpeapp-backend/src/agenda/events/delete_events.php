<?php

include_once __DIR__ . '/../../utils.php';

function apiDeleteEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';

    $id = $request->get_param('id');

    if (empty($id)) {
        return createErrorResponse('missing_params', 'Missing id', 400);
    }

    $result = $wpdb->delete(
        $table_events,
        array(
            'id' => intval($id)
        )
    );

    if ($result === false) {
        return createErrorResponse('db_delete_error', 'Could not delete event', 500);
    }

    return createSuccessResponse(null, 204);
}
<?php

function apiDeleteEventsTypes(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the id from the request body
    $id = $request->get_param('id');

    $response = null;

    if (empty($id)) {
        $response = createErrorResponse('missing_params', 'Missing id', 400);
    } else {
        // Check if an event has this type
        $table_events = $wpdb->prefix . 'agenda_events';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events WHERE type_id = %d", intval($id)));
        if ($exists > 0) {
            $response = createErrorResponse('event_exists', 'Cannot delete because event type has assigned events', 409);
        } else {
            // Delete the event type from the database
            $result = $wpdb->delete(
                $table_events_type,
                array(
                    'id' => intval($id)
                )
            );

            // Check if the delete was successful
            if ($result === false) {
                $response = createErrorResponse('db_delete_error', 'Could not delete event type', 500);
            } else {
                // Return a 204 response
                $response = createSuccessResponse('Event type deleted', 204);
            }
        }
    }

    return $response;
}
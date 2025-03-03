<?php

function apiDeleteEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';

    // Get the id from the request body
    $id = $request->get_param('id');

    if (empty($id)) {
        return new WP_Error('missing_params', __('Missing id', 'Agenda'), array('status' => 400));
    }

    // Delete the event from the database
    $result = $wpdb->delete(
        $table_events,
        array(
            'id' => intval($id)
        )
    );

    // Check if the delete was successful
    if ($result === false) {
        return new WP_Error('db_delete_error', __('Could not delete event', 'Agenda'), array('status' => 500));
    }

    // Return a 204 response
    return new WP_REST_Response(null, 204);
}
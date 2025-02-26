<?php

function apiPutEventsTypes(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // get the id and label from the request body
    $id = $request->get_param('id');
    $label = $request->get_param('label');

    if (empty($id) || empty($label)) {
        return new WP_Error('missing_params', __('Missing id or label', 'Agenda'), array('status' => 400));
    }



    // Check if the event type exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events_type WHERE id = %d", intval($id)));

    if ($exists == 0) {
        return new WP_Error('not_found', __('Event type not found', 'Agenda'), array('status' => 404));
    }

    // Update the event type in the database
    $result = $wpdb->update(
        $table_events_type,
        array(
            'label' => $label
        ),
        array(
            'id' => intval($id)
        ),
    );
    // Check if the update was successful
    if ($result === false) {
        return new WP_Error('db_update_error', __('Could not update event type', 'Agenda'), array('status' => 500));
    }
    
    // Return a 204 response
    return new WP_REST_Response(null, 204);
}
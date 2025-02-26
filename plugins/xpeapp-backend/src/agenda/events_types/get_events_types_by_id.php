<?php

function apiGetEventsTypesById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $id = $request->get_param('id');

    // Get the id from the request body
    if (empty($id)) {
        return new WP_Error('noParams', __('No parameters for id', 'Agenda'), array('status' => 400));
    }

    // Check if the event type exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events_type WHERE id = %d", intval($id)));

    if ($exists == 0) {
        return new WP_Error('not_found', __('Event type not found', 'Agenda'), array('status' => 404));
    }

    // Get the event type from the database
    $query = $wpdb->prepare("SELECT label FROM $table_events_type WHERE id = %d", intval($id));
    $events_type = $wpdb->get_row($query);

    // Check if the event type was found
    if ($events_type) {
        return $events_type;
    } else {
        // Return an error if the event type was not found
        return new WP_Error('not_found', __('Error finding event type', 'Agenda'), array('status' => 404));
    }
}
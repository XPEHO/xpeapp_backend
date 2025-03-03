<?php

function apiGetEventsById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $id = $request->get_param('id');

    // Get the id from the request body
    if (empty($id)) {
        return new WP_Error('noParams', __('No parameters for id', 'Agenda'), array('status' => 400));
    }

    // Check if the event exists
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events WHERE id = %d", intval($id)));

    if ($exists == 0) {
        return new WP_Error('not_found', __('Event not found', 'Agenda'), array('status' => 404));
    }

    // Get the event and its type from the database
    $query = $wpdb->prepare("
        SELECT te.date, te.heure_debut, te.heure_fin, te.titre, te.lieu, te.topic, tet.label as type_label 
        FROM $table_events te
        LEFT JOIN $table_events_type tet ON te.type_id = tet.id
        WHERE te.id = %d
    ", intval($id));
    $event = $wpdb->get_row($query);

    // Check if the event was found
    if ($event) {
        return $event;
    } else {
        // Return an error if the event was not found
        return new WP_Error('not_found', __('Error finding event', 'Agenda'), array('status' => 404));
    }
}
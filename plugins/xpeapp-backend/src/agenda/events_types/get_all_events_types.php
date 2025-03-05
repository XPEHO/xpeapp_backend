<?php

function apiGetAllEventsTypes(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $query = $wpdb->prepare("SELECT * FROM $table_events_type");
    return $wpdb->get_results($query);
    
}
<?php

function apiGetAllEventsTypes(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $query = $wpdb->prepare("SELECT label FROM $table_events_type");
    $events_type = $wpdb->get_results($query);

    return $events_type;
    
}
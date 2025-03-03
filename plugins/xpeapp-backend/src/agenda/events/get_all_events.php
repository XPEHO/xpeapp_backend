<?php

function apiGetAllEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $query = $wpdb->prepare("
        SELECT te.date, te.heure_debut, te.heure_fin, te.titre, te.lieu, te.topic, tet.label as type_label 
        FROM $table_events te
        LEFT JOIN $table_events_type tet ON te.type_id = tet.id
    ");
    
    return $wpdb->get_results($query);
}
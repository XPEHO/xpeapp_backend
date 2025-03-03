<?php

include_once __DIR__ . '/../../utils.php';

function apiGetAllBirthdays(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $query = $wpdb->prepare("
        SELECT first_name, birthdate, email
        FROM $table_birthday
    ");
    
    return $wpdb->get_results($query);
}
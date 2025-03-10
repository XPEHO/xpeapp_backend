<?php

include_once __DIR__ . '/../../utils.php';

function apiGetAllEvents(WP_REST_Request $request) {
    // Define allowed origins based on environment
    $allowed_origins = getenv_docker('CORS_ALLOWED_ORIGINS', '');
    $allowed_origins = explode(';', $allowed_origins);

    // Get the origin of the request
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Add CORS headers if the origin is allowed
    if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
    }
    // Add other CORS headers
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the page parameter from the query parameters
    $page = $request->get_param('page');

    // Define custom query
    $custom_query = "
        SELECT e.*, et.id as type_id, et.label as type_label, et.color_code as type_color_code
        FROM $table_events e
        LEFT JOIN $table_events_type et ON e.type_id = et.id";

    // Build the query using the utility function
    $query = buildQueryWithPaginationAndFilters($table_events, $page, 'date', 10, $custom_query);

    $results = $wpdb->get_results($query);

    // Format the results to include event type as an object and return directly
    return array_map(function($event) {
        return (object) [
            'id' => $event->id,
            'title' => $event->title,
            'date' => $event->date,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'location' => $event->location,
            'topic' => $event->topic,
            'type' => (object) [
                'id' => $event->type_id,
                'label' => $event->type_label,
                'color_code' => $event->type_color_code
            ]
        ];
    }, $results);
}
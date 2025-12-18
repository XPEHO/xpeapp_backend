<?php

namespace XpeApp\agenda\events;

class GetAllEvents {
    public static function apiGetAllEvents(\WP_REST_Request $request)
    {

        error_log('getAllEvents called');
        
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

        // Get the page parameter from the query parameters
        $page = $request->get_param('page');

        // Build the query using the utility function
        $query = buildQueryWithPaginationAndFilters($table_events, $page, 'date');

        return $wpdb->get_results($query);
    }
}

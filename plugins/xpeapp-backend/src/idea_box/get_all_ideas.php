<?php

include_once __DIR__ . '/../utils.php';

function apiGetAllIdeas(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;
    $table_idea_box = $wpdb->prefix . 'idea_box';

    // Get the page parameter from the query parameters
    $page = $request->get_param('page');

    // Build the query using the utility function
    $query = buildQueryWithPaginationAndFilters($table_idea_box, $page, 'created_at');

    return $wpdb->get_results($query);
}
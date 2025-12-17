<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetAllIdeas {
    public static function apiGetAllIdeas(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;
    $table_idea_box = $wpdb->prefix . 'idea_box';

    // Get the page and status parameters from the query parameters
    $page = $request->get_param('page');
    $status = $request->get_param('status');

    // Build custom query with status filter if provided
    $custom_query = null;
    if (!empty($status) && in_array($status, ['pending', 'approved', 'implemented', 'rejected'])) {
        $custom_query = $wpdb->prepare("SELECT * FROM $table_idea_box WHERE status = %s", $status);
    }

    // Build the query using the utility function
    $query = buildQueryWithPaginationAndFilters($table_idea_box, $page, 'created_at', 10, $custom_query);

    return $wpdb->get_results($query);
}
}

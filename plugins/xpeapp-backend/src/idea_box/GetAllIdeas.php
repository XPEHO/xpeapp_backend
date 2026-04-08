<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetAllIdeas {
    private static $validStatuses = ['pending', 'approved', 'implemented', 'rejected'];

    public static function apiGetAllIdeas(WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        /** @var wpdb $wpdb */
        global $wpdb;

        // Table names
        $table_idea_box = $wpdb->prefix . 'idea_box';
        $table_usermeta = $wpdb->prefix . 'usermeta';

        // Get query parameters
        $page = $request->get_param('page');
        $status = $request->get_param('status');

        // Build SQL query with author join
        $sql = "
            SELECT
                i.*,
                CONCAT_WS(' ', fn.meta_value, ln.meta_value) AS author
            FROM {$table_idea_box} i
            LEFT JOIN {$table_usermeta} fn
                ON i.user_id = fn.user_id AND fn.meta_key = 'first_name'
            LEFT JOIN {$table_usermeta} ln
                ON i.user_id = ln.user_id AND ln.meta_key = 'last_name'
        ";

        // Add status filter if valid
        if (!empty($status) && in_array($status, self::$validStatuses, true)) {
            $sql = $wpdb->prepare($sql . " WHERE i.status = %s", $status);
        }

        // Apply pagination and ordering
        $query = buildQueryWithPaginationAndFilters($table_idea_box, $page, 'i.created_at', 10, $sql);

        return $wpdb->get_results($query);
    }
}

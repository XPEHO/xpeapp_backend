<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetIdeaById {
    public static function apiGetIdeaById(WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        /** @var wpdb $wpdb */
        global $wpdb;

        // Table names
        $table_idea_box = $wpdb->prefix . 'idea_box';
        $table_usermeta = $wpdb->prefix . 'usermeta';

        $id = $request->get_param('id');

        // Validate id parameter
        if (empty($id)) {
            return createErrorResponse('missing_id', 'Missing id parameter', 400);
        }

        // Check if the idea exists
        if (!entityExists($id, $table_idea_box)) {
            return createErrorResponse('not_found', 'Idea not found', 404);
        }

        // Build SQL query with author join
        $sql = $wpdb->prepare("
            SELECT 
                i.*,
                CONCAT_WS(' ', fn.meta_value, ln.meta_value) AS author
            FROM {$table_idea_box} i
            LEFT JOIN {$table_usermeta} fn 
                ON i.user_id = fn.user_id AND fn.meta_key = 'first_name'
            LEFT JOIN {$table_usermeta} ln 
                ON i.user_id = ln.user_id AND ln.meta_key = 'last_name'
            WHERE i.id = %d
        ", intval($id));

        $idea = $wpdb->get_row($sql);

        if ($idea) {
            return createSuccessResponse($idea);
        }

        return createErrorResponse('db_get_error', 'Could not get idea', 400);
    }
}

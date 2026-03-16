<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetMyIdeas {
    public static function apiGetMyIdeas(WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;
        $table_idea_box = $wpdb->prefix . 'idea_box';

        $current_user_id = get_current_user_id();
        $page = $request->get_param('page');
        $status = $request->get_param('status');

        if (empty($current_user_id)) {
            return createErrorResponse('unauthorized', 'User not authenticated', 401);
        }

        $custom_query = $wpdb->prepare(
            "SELECT * FROM $table_idea_box WHERE user_id = %d",
            intval($current_user_id)
        );

        if (!empty($status) && in_array($status, ['pending', 'approved', 'implemented', 'rejected'], true)) {
            $custom_query = $wpdb->prepare(
                "SELECT * FROM $table_idea_box WHERE user_id = %d AND status = %s",
                intval($current_user_id),
                $status
            );
        }

        $query = buildQueryWithPaginationAndFilters($table_idea_box, $page, 'created_at', 10, $custom_query);

        return $wpdb->get_results($query);
    }
}
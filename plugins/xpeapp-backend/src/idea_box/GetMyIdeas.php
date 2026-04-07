<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetMyIdeas {
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_IMPLEMENTED = 'implemented';
    public const STATUS_REJECTED = 'rejected';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_IMPLEMENTED,
        self::STATUS_REJECTED,
    ];

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

        $sql = "SELECT * FROM {$table_idea_box} WHERE user_id = %d";
        $args = [intval($current_user_id)];

        if (!empty($status) && in_array($status, self::VALID_STATUSES, true)) {
            $sql .= " AND status = %s";
            $args[] = $status;
        }

        $custom_query = $wpdb->prepare($sql, ...$args);

        $query = buildQueryWithPaginationAndFilters($table_idea_box, $page, 'created_at', 10, $custom_query);

        return $wpdb->get_results($query);
    }
}
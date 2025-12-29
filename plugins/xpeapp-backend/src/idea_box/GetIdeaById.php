<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class GetIdeaById {
    public static function apiGetIdeaById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_idea_box = $wpdb->prefix . 'idea_box';

    $id = $request->get_param('id');

    // Check if the parameters are valid
    if (empty($id)) {
        $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
    // Check if the idea exists
    } elseif (!entityExists($id, $table_idea_box)) {
        $response = createErrorResponse('not_found', 'Idea not found', 404);
    } else {
        // Get the idea from the database
        $query = $wpdb->prepare("
            SELECT *
            FROM $table_idea_box
            WHERE id = %d
        ", intval($id));
        $idea = $wpdb->get_row($query);

        if ($idea) {
            $response = createSuccessResponse($idea);
        } else {
            $response = createErrorResponse('db_get_error', 'Could not get idea', 400);
        }
    }

    return $response;
}
}

<?php

include_once __DIR__ . '/../utils.php';

function apiDeleteIdea(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;
    $table_idea_box = $wpdb->prefix . 'idea_box';

    $id = $request->get_param('id');

    // Check if the parameters are valid
    if (empty($id) || !is_numeric($id)) {
        $response = createErrorResponse('missing_id', 'Missing or invalid idea ID', 400);
    // Check if the idea exists
    } elseif (!entityExists($id, $table_idea_box)) {
        $response = createErrorResponse('not_found', 'Idea not found', 404);
    } else {
        // Delete the idea from the database
        $result = $wpdb->delete(
            $table_idea_box,
            array('id' => intval($id)),
            array('%d')
        );

        if ($result === false) {
            $response = createErrorResponse('db_delete_error', 'Could not delete idea', 500);
        } else {
            $response = createSuccessResponse(null, 204);
        }
    }

    return $response;
}
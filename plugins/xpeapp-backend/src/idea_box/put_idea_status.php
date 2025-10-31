<?php

include_once __DIR__ . '/../utils.php';

function apiPutIdeaStatus(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;
    $table_idea_box = $wpdb->prefix . 'idea_box';

    $id = $request->get_param('id');
    $params = $request->get_params();

    // Check if the parameters are valid
    if (empty($id)) {
        $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
    } elseif (empty($params['status'])) {
        $response = createErrorResponse('missing_status', 'Missing status parameter', 400);
    } elseif (!in_array($params['status'], ['pending', 'approved', 'implemented', 'rejected'])) {
        $response = createErrorResponse('invalid_status', 'Invalid status value', 400);
    // Check if the idea exists
    } elseif (!entityExists($id, $table_idea_box)) {
        $response = createErrorResponse('not_found', 'Idea not found', 404);
    } else {
        // Update the idea status in the database using prepareData like birthday
        $result = $wpdb->update($table_idea_box, prepareData($params, ['status']), ['id' => intval($id)]);

        if ($result === false) {
            $response = createErrorResponse('db_update_error', 'Could not update idea status', 500);
        } else {
            $response = createSuccessResponse(null, 204);
        }
    }

    return $response;
}
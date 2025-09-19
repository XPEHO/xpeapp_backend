<?php

include_once __DIR__ . '/../utils.php';

// Fetch all images, optionally filtered by folder
function apiGetAllFoldersOrImagesByFolder(WP_REST_Request $request) {

    xpeapp_log_request($request);

    global $wpdb;
    $table = $wpdb->prefix . 'images';
    $folder = $request->get_param('folder');
    $response = null;

    if ($folder) {
        // If a folder is specified, return all images in that folder
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, folder, filename, mime_type, uploaded_at FROM $table WHERE folder = %s",
            $folder
        ));
        if ($results === false) {
            $response = createErrorResponse('db_error', 'Database error', 500);
        } else {
            $response = createSuccessResponse($results, 200);
        }
    } else {
        // If no folder is specified, return a list of all unique folders
        $folders = $wpdb->get_col("SELECT DISTINCT folder FROM $table");
        if ($folders === false) {
            $response = createErrorResponse('db_error', 'Database error', 500);
        } else {
            $response = createSuccessResponse($folders, 200);
        }
    }
    return $response;
}

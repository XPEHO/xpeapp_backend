<?php

// Fetch all images, optionally filtered by folder
function apiGetAllFoldersOrImagesByFolder($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'images';
    $folder = $request->get_param('folder');

    if ($folder) {
        // If a folder is specified, return all images in that folder
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, folder, filename, mime_type, uploaded_at FROM $table WHERE folder = %s",
            $folder
        ));
        return new WP_REST_Response($results, 200);
    } else {
        // If no folder is specified, return a list of all unique folders
        $folders = $wpdb->get_col("SELECT DISTINCT folder FROM $table");
        return new WP_REST_Response($folders, 200);
    }
}
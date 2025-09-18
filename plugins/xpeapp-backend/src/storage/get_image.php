<?php

// Fetch an image by folder and filename
function apiGetImage($request) {
    $params = $request->get_params();
    if (empty($params['folder']) || empty($params['filename'])) {
        return new WP_REST_Response(['error' => 'Missing folder or filename'], 400);
    }
    $folder = $params['folder'];
    $filename = $params['filename'];
    global $wpdb;
    $table = $wpdb->prefix . 'images';
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT filename, mime_type, data FROM $table WHERE folder = %s AND filename = %s",
        $folder, $filename
    ));
    if (!$row) {
        return new WP_REST_Response(['error' => 'Image not found'], 404);
    }
-
    header('Content-Type: ' . $row->mime_type);
    header('Content-Disposition: attachment; filename="' . $row->filename . '"');
    header('Content-Length: ' . strlen($row->data));
    echo $row->data;
    exit;
}
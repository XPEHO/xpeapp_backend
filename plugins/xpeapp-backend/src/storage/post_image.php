<?php
function apiPostImage($request) {
    $files = $request->get_file_params();
    $folder = $request->get_param('folder');
    $response = null;

    if (empty($files['file']) || empty($folder)) {
        $response = new WP_REST_Response(['error' => 'Missing file or folder'], 400);
    } elseif (empty($files['file']['tmp_name'])) {
        $response = new WP_REST_Response([
            'error' => 'File upload failed',
            'debug' => $files
        ], 400);
    } else {
        $filename = sanitize_file_name($files['file']['name']);
        $mime_type = sanitize_text_field($files['file']['type']);
        $data = file_get_contents($files['file']['tmp_name']);

        global $wpdb;
        $table = $wpdb->prefix . 'images';
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (folder, filename, mime_type, data) VALUES (%s, %s, %s, %s)",
            $folder, $filename, $mime_type, $data
        ));

        if ($result === false) {
            $response = new WP_REST_Response(['error' => 'DB insert failed'], 500);
        } else {
            $response = new WP_REST_Response(['success' => true], 201);
        }
    }
    return $response;
}
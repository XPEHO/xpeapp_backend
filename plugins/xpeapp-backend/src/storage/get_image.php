<?php

// Fetch an image by folder and filename (binaire direct, SonarQube friendly)
// Write all the comments in english
function apiGetImage($request) {
    $params = $request->get_params();
    if (empty($params['folder']) || empty($params['filename'])) {
        return wp_send_json_error(['error' => 'Missing folder or filename'], 400);
    }

    // Sanitize inputs
    $folder   = sanitize_text_field($params['folder']);
    $filename = sanitize_file_name($params['filename']);
    global $wpdb;
    $table = $wpdb->prefix . 'images';

    // Secure retrieval from the database
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT filename, mime_type, data FROM $table WHERE folder = %s AND filename = %s",
        $folder,
        $filename
    ));
    if (!$row) {
        return wp_send_json_error(['error' => 'Image not found'], 404);
    }

    // Clean the output buffer to prevent any unintended output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send headers securely
    $mime_type = esc_attr($row->mime_type);
    $safe_name = esc_attr($row->filename);
    $length    = strlen($row->data);

    header("Content-Type: {$mime_type}");
    header("Content-Disposition: attachment; filename=\"{$safe_name}\"");
    header("Content-Length: {$length}");
    header('Cache-Control: public, max-age=86400');

    // Send the binary data
    echo $row->data;

    // Terminate cleanly without generating SonarQube errors
    exit(0);
}

<?php

include_once __DIR__ . '/../../utils.php';

// Fetch an image by folder and filename (binary direct)
function apiGetImage($request) {
    $params = $request->get_params();
    $response = null;

    // Validate parameters
    if (empty($params['folder']) || empty($params['filename'])) {
        $response = createErrorResponse('missing_parameters', 'Missing folder or filename', 400);
    } else {
        // Sanitize inputs
        $folder = sanitize_text_field($params['folder']);
        $filename = sanitize_file_name($params['filename']);
        global $wpdb;
        $table = $wpdb->prefix . 'images';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT filename, mime_type, data FROM $table WHERE folder = %s AND filename = %s",
            $folder,
            $filename
        ));
        if (!$row) {
            $response = createErrorResponse('not_found', 'Image not found', 404);
        } else {
            // Clean the output buffer to prevent any unintended output
            while (ob_get_level()) {
                ob_end_clean();
            }
            // set headers
            $mime_type = esc_attr($row->mime_type);
            $safe_name = esc_attr($row->filename);
            $length    = strlen($row->data);

            header("Content-Type: {$mime_type}");
            header("Content-Disposition: attachment; filename=\"{$safe_name}\"");
            header("Content-Length: {$length}");
            header('Cache-Control: public, max-age=86400');

            // Send the binary data
            echo $row->data;
            exit(0);
        }
    }
    return $response;
}

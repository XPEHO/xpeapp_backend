<?php
namespace XpeApp\storage;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

// Upload an image to a folder

class PostImage {
    public static function apiPostImage(WP_REST_Request $request) {

    xpeapp_log_request($request);

    $files = $request->get_file_params();
    $folder = $request->get_param('folder');
    $response = null;

    if (empty($files['file']) || empty($folder)) {
        $response = createErrorResponse('missing_file_or_folder', 'Missing file or folder', 400);
    } elseif (empty($files['file']['tmp_name'])) {
        $response = createErrorResponse('file_upload_failed', 'File upload failed', 400);
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
            $response = createErrorResponse('db_insert_failed', 'Cannot insert image in the storage', 500);
        } else {
            $response = createSuccessResponse(null, 201);
        }
    }
    return $response;
    }
}

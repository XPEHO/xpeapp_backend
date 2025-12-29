<?php

namespace XpeApp\storage;

class ImageController {
    public static function apiPostImage(\WP_REST_Request $request) {

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

    public static function apiGetImage(\WP_REST_Request $request) {

    xpeapp_log_request($request);

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

    public static function apiGetAllFoldersOrImagesByFolder(\WP_REST_Request $request) {

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

    public static function apiDeleteImage(\WP_REST_Request $request) {
    
    xpeapp_log_request($request);

    $id = $request->get_param('id');
    $response = null;

    // Validate parameter
    if (empty($id)) {
        $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
    } else {
        global $wpdb;
        $table = $wpdb->prefix . 'images';
        $deleted = $wpdb->delete($table, ['id' => intval($id)]);
        if ($deleted) {
            $response = createSuccessResponse(null, 204);
        } else {
            $response = createErrorResponse('not_found', 'Image not found or not deleted', 404);
        }
    }
    return $response;
    }
}

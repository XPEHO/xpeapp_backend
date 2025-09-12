<?php

function apiGetImageById($request) {
    $params = $request->get_params();
    if (!isset($params['id'])) {
        return new WP_REST_Response(['error' => 'Missing id'], 400);
    }
    $id = intval($params['id']);
    global $wpdb;
    $table = $wpdb->prefix . 'images';
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT filename, mime_type, data FROM $table WHERE id = %d",
        $id
    ));
    if (!$row) {
        return new WP_REST_Response(['error' => 'Image not found'], 404);
    }
    return new WP_REST_Response($row->data, 200, [
        'Content-Type' => $row->mime_type,
        'Content-Disposition' => 'attachment; filename="' . $row->filename . '"'
    ]);
}
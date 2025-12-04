<?php

// Permite to insert a FCM send log into the database for tracking purposes of notification sends.
function xpeapp_insert_fcm_send($payload) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'fcm_sends';
    $wpdb->insert(
        $table_name,
        array(
            'created_at' => current_time('mysql'),
            'title' => (string)($payload['title'] ?? ''),
            'message' => (string)($payload['message'] ?? ''),
            'status' => (string)($payload['status'] ?? ''),
            'error' => isset($payload['error']) ? (string)$payload['error'] : null,
        ),
        array('%s','%s','%s','%s','%s')
    );
}

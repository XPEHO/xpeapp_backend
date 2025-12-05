<?php

// Permite to insert a FCM send log into the database for tracking purposes of notification sends.
function xpeappInsertFcmSend($payload) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications_sent';
    $wpdb->insert(
        $table_name,
        array(
            'created_at' => current_time('mysql'),
            'title' => (string)($payload['title'] ?? ''),
            'message' => (string)($payload['message'] ?? ''),
        ),
        array('%s','%s','%s')
    );
}

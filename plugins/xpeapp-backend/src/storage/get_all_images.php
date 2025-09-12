<?php

// Renvoit toutes les images stockées dans la base de données sous forme de JSON (à voir si c'est utile...)

function apiGetAllImages($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'images';
    $results = $wpdb->get_results("SELECT id, folder, filename, mime_type, uploaded_at FROM $table");
    return new WP_REST_Response($results, 200);
}
<?php

function apiDeleteImage($request) {
	$id = $request->get_param('id');
	if (empty($id)) {
		return new WP_REST_Response(['error' => 'Missing id'], 400);
	}
	global $wpdb;
	$table = $wpdb->prefix . 'images';
	$deleted = $wpdb->delete($table, ['id' => intval($id)]);
	if ($deleted) {
		return new WP_REST_Response(['success' => true], 200);
	} else {
		return new WP_REST_Response(['error' => 'Image not found or not deleted'], 404);
	}
}

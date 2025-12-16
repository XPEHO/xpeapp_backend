<?php
namespace XpeApp\storage;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

// Delete an image by ID

class delete_image {
    public static function apiDeleteImage(WP_REST_Request $request) {
    
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
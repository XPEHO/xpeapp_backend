<?php

namespace XpeApp\qvst\questions;

class DeleteQvstQuestion {
	public static function apiDeleteQvst(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';

	$id = intval($request->get_param('id'));

	// check parameters
	if (empty($id)) {
		return new \WP_Error('noID', __('No question id provided', 'QVST'));
	}

	// check if the question exists (use prepared statement)
	$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_questions WHERE id = %d", $id));

	if (empty($question)) {
		return new \WP_Error('noID', __('No question found', 'QVST'));
	}

	// delete question
	$wpdb->delete($table_name_questions, array('id' => $question->id));

	// return 204 no content status code if success
	return new \WP_REST_Response(null, 204);
}
}
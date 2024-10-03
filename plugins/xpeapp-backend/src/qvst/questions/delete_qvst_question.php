<?php 

function api_delete_qvst(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';

	$request->get_param('id');

	// check if the question exists
	$question = $wpdb->get_row("SELECT * FROM $table_name_questions WHERE id=" . $request->get_param('id'));

	if (empty($question)) {
		return new WP_Error('noID', __('No question found', 'QVST'));
	}

	// delete question
	$wpdb->delete($table_name_questions, array('id' => $question->id));

	// return 204 no content status code if success
	return new WP_REST_Response(null, 204);
}
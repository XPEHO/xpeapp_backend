<?php

function postOpenAnswers(WP_REST_Request $request) {

    xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$params = $request->get_params();
	$token = $request->get_header('Authorization');

	$table_open_answers = $wpdb->prefix . 'qvst_open_answers';

	$params = $request->get_params();

	if (empty($params)) {
		return new WP_Error('noParams', __('No parameters found', 'QVST'));

	} else if (!isset($params['answer_group_id'])) {
		return new WP_Error('noAnswerGroupId', __('No answer group id found', 'QVST'));

	} else if (!isset($params['text'])) {
		return new WP_Error('noText', __('No text found', 'QVST'));
	} else {

		try {
			$openAnswersToInsert = array(
				'answer_group_id' => $token,
				'text' => $params['text'],
			);

			// save openAnswers
			$wpdb->insert(
				$table_open_answers,
				$openAnswersToInsert,
			);

			// return 201 created status code if success
			return new WP_REST_Response(null, 201);
		} catch (\Throwable $th) {
			return new WP_Error('error', __('Error', 'QVST'));
		}
	}
}

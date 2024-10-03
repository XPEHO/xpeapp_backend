<?php

function api_update_answers_repo(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';

	$params = $request->get_params();
	$body = json_decode($request->get_body());

	if (empty($params) || empty($body)) {
		return new WP_Error('noParams', __('No parameters or body', 'QVST'));
	} else {
		try {

			// Check if the repo exists
			$repo = $wpdb->get_row("SELECT * FROM $table_name_answers_repository WHERE id=" . $params['id']);
			if (empty($repo)) {
				return new WP_Error('noID', __('No repository found', 'QVST'));
			} else {
				// Update name of the repo with repoName in the body
				$wpdb->update(
					$table_name_answers_repository,
					array(
						'name' => $body->repoName
					),
					array(
						'id' => $params['id']
					)
				);
			}

			// Get the list of answers by answer_repo_id
			$answers = $wpdb->get_results("SELECT * FROM $table_name_answers WHERE answer_repo_id=" . $params['id']);

			if (empty($answers)) {
				return new WP_Error('noID', __('No answers found', 'QVST'));
			} else {
				if (empty($body->answers)) {
					return new WP_Error('noAnswers', __('No answers in the body', 'QVST'));
				} else {
					// Update answers
					foreach ($body->answers as $answer) {
						$wpdb->update(
							$table_name_answers,
							array(
								'name' => $answer->answer,
								'value' => $answer->value
							),
							array(
								'id' => $answer->id
							)
						);
					}
				}
			}

			return new WP_REST_Response(null, 201);

		} catch (\Throwable $th) {
			echo $th;
			return new WP_Error('error', __('Error', 'QVST'));
		}
	}
}
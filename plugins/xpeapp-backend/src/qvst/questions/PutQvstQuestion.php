<?php

namespace XpeApp\qvst\questions;

class PutQvstQuestion {
	public static function apiUpdateQuestion(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';

	$params = $request->get_params();
	$id_question = isset($params['id']) ? intval($params['id']) : 0;
	$body = json_decode($request->get_body());


	// Check if the question exists (prepared)
	$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_questions} WHERE id = %d", $id_question));
	if (empty($question)) {
		return new \WP_Error('noID', __('No question found', 'QVST'));
	}

	if (empty($params) || empty($body)) {
		return new \WP_Error('noParams', __('No parameters or body', 'QVST'));
	} else {
		try {

			// Check if the theme exists if the theme is in the body
			if (isset($body->theme_id)) {
				$theme_id = intval($body->theme_id);
				$theme = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_theme} WHERE id = %d", $theme_id));
				if (empty($theme)) {
					return new \WP_Error('noID', __('No theme found', 'QVST'));
				}
			}

			// Check if the answers repo exists if the answers repo is in the body
			if (isset($body->answer_repo_id)) {
				$repo_id = intval($body->answer_repo_id);
				$repo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_answers_repository} WHERE id = %d", $repo_id));
				if (empty($repo)) {
					return new \WP_Error('noID', __('No repository found', 'QVST'));
				}
			}

			if (empty($question)) {
				return new \WP_Error('noID', __('No question found', 'QVST'));
			} else {
				// Define the new values
				$questionToInsert = array(
					'text' => $body->question ?? $question->text,
					'theme_id' => $body->theme_id ?? $question->theme_id,
					'answer_repo_id' => $body->answer_repo_id ?? $question->answer_repo_id
				);

				// Handle optional boolean fields
				if (isset($body->reversed_question)) {
					$questionToInsert['reversed_question'] = (int) filter_var($body->reversed_question, FILTER_VALIDATE_BOOLEAN);
				}
				if (isset($body->no_longer_used)) {
					$questionToInsert['no_longer_used'] = (int) filter_var($body->no_longer_used, FILTER_VALIDATE_BOOLEAN);
				}

				// Update question with question in the body
				$wpdb->update(
					$table_name_questions,
					$questionToInsert,
					array(
						'id' => $id_question
					)
				);
			}

			// Get the question updated (prepared)
			$questionUpdated = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_questions} WHERE id = %d", $id_question));

			return new \WP_REST_Response($questionUpdated, 201);

		} catch (\Throwable $th) {
			echo $th;
			return new \WP_Error('error', __('Error', 'QVST'));
		}
	}
}
}
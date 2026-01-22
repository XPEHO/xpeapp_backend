<?php

namespace XpeApp\qvst\questions;

class PostQvstQuestion {
	public static function apiPostQvst(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';

	$params = $request->get_params();

	if (empty($params)) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No parameters");
		return new \WP_Error('noParams', __('No parameters', 'QVST'));

	} else if (!isset($params['question'])) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No question parameter");
		return new \WP_Error('noQuestion', __('No question', 'QVST'));

	} else if (!isset($params['theme'])) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme parameter");
		return new \WP_Error('noTheme', __('No theme', 'QVST'));

	} else if (!isset($params['theme_id'])) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme ID parameter");
		return new \WP_Error('noThemeID', __('No theme ID', 'QVST'));

	} else {

		try {
			// sanitize and prepare ids
			$theme_id = intval($params['theme_id']);
			$repo_id = isset($params['answer_repo_id']) ? intval($params['answer_repo_id']) : 0;

			// get theme with id (prepared)
			$theme = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_theme} WHERE id = %d", $theme_id));
			if (empty($theme)) {
				xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme found for ID: " . $theme_id);
				return new \WP_Error('noID', __('No theme found', 'QVST'));
			}

			$repo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_answers_repository} WHERE id = %d", $repo_id));
			if (empty($repo)) {
				xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No repository found for ID: " . $repo_id);
				return new \WP_Error('noID', __('No repository found', 'QVST'));
			}

			// Handle optional boolean fields
			$reversed_question = isset($params['reversed_question']) ? (int) filter_var($params['reversed_question'], FILTER_VALIDATE_BOOLEAN) : 0;
			$no_longer_used = isset($params['no_longer_used']) ? (int) filter_var($params['no_longer_used'], FILTER_VALIDATE_BOOLEAN) : 0;

			$questionToInsert = array(
				'text' => $params['question'],
				'theme_id' => $theme_id,
				'answer_repo_id' => $repo_id,
				'reversed_question' => $reversed_question,
				'no_longer_used' => $no_longer_used
			);

			// save question
			$wpdb->insert(
				$table_name_questions,
				$questionToInsert,
			);

			// return 201 created status code if success
			return new \WP_REST_Response(null, 201);
		} catch (\Throwable $th) {
			xpeapp_log(\Xpeapp_Log_Level::Error, "POST xpeho/v1/qvst:add - Error occurred: " . $th->getMessage());
			return new \WP_Error('error', __('Error', 'QVST'));
		}
	}
}
}
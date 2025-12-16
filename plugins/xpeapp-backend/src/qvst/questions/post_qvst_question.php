<?php

namespace XpeApp\qvst\questions;

class post_qvst_question {
	public static function api_post_qvst(\WP_REST_Request $request)
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
		xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No parameters");
		return new WP_Error('noParams', __('No parameters', 'QVST'));

	} else if (!isset($params['question'])) {
		xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No question parameter");
		return new WP_Error('noQuestion', __('No question', 'QVST'));

	} else if (!isset($params['theme'])) {
		xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme parameter");
		return new WP_Error('noTheme', __('No theme', 'QVST'));

	} else if (!isset($params['theme_id'])) {
		xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme ID parameter");
		return new WP_Error('noThemeID', __('No theme ID', 'QVST'));

	} else {

		try {
			// get theme with id
			$theme = $wpdb->get_row("SELECT * FROM $table_name_theme WHERE id=" . $params['theme_id']);
			if (empty($theme)) {
				xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No theme found for ID: " . $params['theme_id']);
				return new WP_Error('noID', __('No theme found', 'QVST'));
			}

			$repo = $wpdb->get_row("SELECT * FROM $table_name_answers_repository WHERE id=" . $params['answer_repo_id']);
			if (empty($repo)) {
				xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:add - No repository found for ID: " . $params['answer_repo_id']);
				return new WP_Error('noID', __('No repository found', 'QVST'));
			}

			$questionToInsert = array(
				'text' => $params['question'],
				'theme_id' => $params['theme_id'],
				'answer_repo_id' => $params['answer_repo_id']
			);

			// save question
			$wpdb->insert(
				$table_name_questions,
				$questionToInsert,
			);

			// return 201 created status code if success
			return new WP_REST_Response(null, 201);
		} catch (\Throwable $th) {
			xpeapp_log(Xpeapp_Log_Level::Error, "POST xpeho/v1/qvst:add - Error occurred: " . $th->getMessage());
			return new WP_Error('error', __('Error', 'QVST'));
		}
	}
}
}
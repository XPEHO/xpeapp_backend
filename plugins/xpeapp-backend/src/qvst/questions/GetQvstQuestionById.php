<?php

namespace XpeApp\qvst\questions;

class GetQvstQuestionById {
	public static function apiGetQvstById(\WP_REST_Request $request)
{
	xpeapp_log_request($request);

	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';

	$params = $request->get_params();

	if (!empty($params)) {
		if (!isset($params['id'])) {
			xpeapp_log(\Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/{id} - No ID parameter");
			return new \WP_Error('noID', __('No ID', 'QVST'));
		} else {
			$question_id = isset($params['id']) ? intval($params['id']) : 0;
			require_once __DIR__ . '/questions_utils.php';
			$details = get_question_details($question_id);

			if (empty($details['meta'])) {
				xpeapp_log(\Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/$question_id - No data found for ID");
				return new \WP_Error('noID', __('No ID', 'QVST'));
			}

			$meta = $details['meta'];
			$listOfAnswers = [];
			foreach ($details['answers'] as $ans) {
				$listOfAnswers[] = [
					'answer' => $ans->name,
					'value' => $ans->value,
				];
			}

			$data = [
				'id' => $meta->id,
				'theme' => $meta->theme,
				'id_theme' => $meta->theme_id,
				'question' => $meta->question,
				'answers' => $listOfAnswers,
			];

			return $data;
		}
	}
}
}
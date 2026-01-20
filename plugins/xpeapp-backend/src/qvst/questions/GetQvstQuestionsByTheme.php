<?php

namespace XpeApp\qvst\questions;

class GetQvstQuestionsByTheme {
	public static function apiGetQvstQuestionsByThemeId(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

	$params = $request->get_params();

	if (!empty($params)) {
		if (!isset($params['id'])) {
			return new \WP_Error('noID', __('No ID', 'QVST'));
		} else {
			// renvoyer le congé concerné (sanitize and prepare)
			$theme_id = isset($params['id']) ? intval($params['id']) : 0;
			$queryAnswer = "
			SELECT
				theme.id as theme_id,
				theme.name as theme_name,
				question.id as question_id,
				question.text as question,
				question.answer_repo_id,
				answers.id as answer_id,
				answers.name,
				answers.value,
				COALESCE(cq.num_occurrences, 0) as numberAsked,
				COALESCE(question.reversed_question, 0) as reversed_question,
				COALESCE(question.no_longer_used, 0) as no_longer_used
			FROM {$table_name_answers} answers
			INNER JOIN {$table_name_questions} question ON question.answer_repo_id = answers.answer_repo_id
			INNER JOIN {$table_name_theme} theme ON question.theme_id = theme.id
			LEFT JOIN (
				SELECT question_id, COUNT(campaign_id) as num_occurrences
				FROM {$table_name_campaign_questions}
				GROUP BY question_id
			) cq ON question.id = cq.question_id
			WHERE theme.id = %d
			";

			// Check if we should include no_longer_used questions (default: false)
			$includeNoLongerUsed = filter_var($request->get_param('include_no_longer_used'), FILTER_VALIDATE_BOOLEAN);
			if (!$includeNoLongerUsed) {
				$queryAnswer .= " AND COALESCE(question.no_longer_used, 0) = 0";
			}

			$resultsAnswer = $wpdb->get_results($wpdb->prepare($queryAnswer, $theme_id));
			// Return all rows
			$data = array();
			foreach ($resultsAnswer as $result) {
				$questionExists = false;
				foreach ($data as &$item) {
					if ($item['question_id'] === $result->question_id) {
						$item['answers'][] = array(
							'id' => $result->answer_id,
							'answer' => $result->name,
							'value' => $result->value
						);
						$questionExists = true;
						break;
					}
				}
				if (!$questionExists) {
					$data[] = array(
						'question_id' => $result->question_id,
						'question' => $result->question,
						'theme' => $result->theme_name,
						'theme_id' => $result->theme_id,
						'answer_repo_id' => $result->answer_repo_id,
						'numberAsked' => intval($result->numberAsked),
						'reversed_question' => (bool) $result->reversed_question,
						'no_longer_used' => (bool) $result->no_longer_used,
						'answers' => array(
							array(
								'id' => $result->answer_id,
								'answer' => $result->name,
								'value' => $result->value
							)
						)
					);
				}
			}
			return $data;
		}
	}
}
}
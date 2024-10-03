<?php

function api_get_qvst_by_id(WP_REST_Request $request)
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
			xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/{id} - No ID parameter");
			return new WP_Error('noID', __('No ID', 'QVST'));
		} else {
			// renvoyer le congé concerné
			$question_id = $params['id'];
			$queryAnswer = "
			SELECT 
				theme.id as 'theme_id',
				theme.name as 'name',
				question.id as 'question_id',
				question.text as 'question', 
				answers.name,
				answers.value 
			FROM $table_name_answers answers
			INNER JOIN $table_name_questions question ON question.answer_repo_id=answers.answer_repo_id
			INNER JOIN $table_name_theme theme ON question.theme_id=theme.id
			WHERE question.id=$question_id
			";

			$resultsAnswer = $wpdb->get_results($queryAnswer);
			// Return one row
			$data = array();
			if ($resultsAnswer) {
				foreach ($resultsAnswer as $result) {
					$listOfAnswers[] = array(
						'answer' => $result->name,
						'value' => $result->value
					);
				}
				$data['id'] = $result->question_id;
				$data['theme'] = $result->theme;
				$data['id_theme'] = $result->theme_id;
				$data['question'] = $result->question;
				$data['answers'] = $listOfAnswers;
				return $data;
			} else {
				xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/$question_id - No data found for ID");
				return new WP_Error('noID', __('No ID', 'QVST'));
			}
		}
	}
}
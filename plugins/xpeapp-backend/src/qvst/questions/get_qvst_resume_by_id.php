<?php

function api_get_qvst_resume_by_id(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	// Todo: Figure out why this is never used.
	$table_name_users_answers = $wpdb->prefix . 'qvst_user_answers';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';

	$params = $request->get_params();

	if (!empty($params)) {
		if (!isset($params['id'])) {
			xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/{id}:resume - No ID parameter");
			return new WP_Error('noID', __('No ID', 'QVST'));
		} else {
			// renvoyer le congé concerné
			$question_id = $params['id'];
			$queryAnswer = "
			SELECT 
				question.id as 'id',
				theme.name as 'theme',
				question.text as 'question',
				COUNT(answer.id) as 'numberOfAnswers',
				ROUND(AVG(answer.value)) as 'averageAnswer',
				MAX(answer.value) AS 'maxValueAnswer'  
			FROM $table_name_answers answer
			INNER JOIN $table_name_questions question ON question.answer_repo_id=answer.answer_repo_id
			INNER JOIN $table_name_theme theme ON theme.id=question.theme_id
			WHERE question.id=$question_id
			";

			$resultsAnswer = $wpdb->get_results($queryAnswer);
			// Return one row
			$data = array();
			foreach ($resultsAnswer as $result) {
				$data['id'] = $result->id;
				$data['theme'] = $result->theme;
				$data['question'] = $result->question;
				$data['numberOfAnswers'] = $result->numberOfAnswers;
				$data['averageAnswer'] = $result->averageAnswer;
				$data['maxValueAnswer'] = $result->maxValueAnswer;
			}
			return $data;
		}
	}
}
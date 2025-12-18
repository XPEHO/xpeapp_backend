<?php

namespace XpeApp\qvst\questions;

class GetListOfQvstQuestions {
	public static function ApiGetQvst(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

	// Requête SQL pour récupérer toutes les lignes de la table
	$query = "
			SELECT
			question.id as 'question_id',
			question.text as 'question', 
			theme.id as 'theme_id',
			theme.name as 'theme_name',
			question.answer_repo_id,
			answer.id,
			answer.name,
			answer.value,
			COALESCE(cq.num_occurrences, 0) as 'numberAsked' 
		FROM $table_name_questions question
		INNER JOIN $table_name_theme theme on question.theme_id=theme.id
		INNER JOIN $table_name_answers answer on answer.answer_repo_id=question.answer_repo_id
		LEFT JOIN (
			SELECT question_id, COUNT(campaign_id) as num_occurrences
			FROM $table_name_campaign_questions
			GROUP BY question_id
		) cq ON question.id = cq.question_id
	";

	if ($request->get_param('id')) {
		$query .= " WHERE question.id=" . $request->get_param('id');
	}


	// Récupérer les résultats de la requête
	$results = $wpdb->get_results($query);

	// Vérifier s'il y a des résultats
	if ($results) {
		// return json data
		$data = array();
		foreach ($results as $result) {
			$questionExists = false;
			foreach ($data as &$item) {
				if ($item['question_id'] === $result->question_id) {
					$item['answers'][] = array(
						'id' => $result->id,
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
					'answers' => array(
						array(
							'id' => $result->id,
							'answer' => $result->name,
							'value' => $result->value
						)
					)
				);
			}
		}
		return $data;
	} else {
		xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst No query result found");
		return new \WP_REST_Response(array(
			"error" => "Not Found",
			"message" => "No QVST have been found"
		), 404);
	}
}
}
<?php

function api_get_answers_repo(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';

	$queryAnswer = "
		SELECT 
			repo.id,
			repo.name as 'repoName',
			answers.id as 'answer_id',
			answers.name as 'answer_name',
            answers.value as 'answer_value'  
		FROM $table_name_answers_repository repo
		INNER JOIN $table_name_answers answers ON answers.answer_repo_id=repo.id
	";

	$resultsAnswer = $wpdb->get_results($queryAnswer);
	// Return all rows
	$data = array();
	foreach ($resultsAnswer as $result) {
		// Group answers by repo
		$answerExists = false;
		foreach ($data as &$item) {
			if ($item['id'] === $result->id) {
				$item['answers'][] = array(
					'id' => $result->answer_id,
					'answer' => $result->answer_name,
					'value' => $result->answer_value
				);
				$answerExists = true;
				break;
			}
		}
		if (!$answerExists) {
			$data[] = array(
				'id' => $result->id,
				'repoName' => $result->repoName,
				'answers' => array(
					array(
						'id' => $result->answer_id,
						'answer' => $result->answer_name,
						'value' => $result->answer_value
					)
				)
			);
		}
	}
	return $data;
}
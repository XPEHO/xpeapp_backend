<?php

namespace XpeApp\qvst\questions;

class GetQuestionsByCampaignAndUser {
	public static function apiGetQuestionsByCampaignIdAndUserId(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';
	$table_name_user_answers = $wpdb->prefix . 'qvst_user_answers';

	$params = $request->get_params();
	$userId = $request->get_header('userId');

	if (!empty($params)) {
		if (!isset($params['id'])) {
			return new \WP_Error('noID', __('No ID', 'QVST'));
		} else {

			// Get the campaign with the id
			$campaign = $wpdb->get_row("SELECT * FROM $table_name_campaigns WHERE id=" . $params['id']);
			if (empty($campaign)) {
				return new \WP_Error('noID', __('No campaign found', 'QVST'));
			} else {

				// Get the questions of the campaign
				$questionsSql = "
				SELECT
					question.id AS 'question_id',
					question.text AS 'question',
					answers.id AS 'answer_id',
					answers.name,
					answers.value,
					IF(user_answers.id IS NOT NULL, 1, 0) AS 'hasAnswered'
				FROM
					$table_name_questions question
					INNER JOIN $table_name_campaign_questions campaigns ON question.id = campaigns.question_id
					INNER JOIN $table_name_answers_repository answersRepo ON question.answer_repo_id = answersRepo.id
					INNER JOIN $table_name_answers answers ON answers.answer_repo_id = answersRepo.id
					LEFT JOIN $table_name_user_answers user_answers
						ON user_answers.campaign_id = campaigns.campaign_id
						AND user_answers.question_id = question.id
						AND user_answers.user_id = $userId
				WHERE
					campaigns.campaign_id = " . $params['id'] . "
				";

				$questions = $wpdb->get_results($questionsSql);

				$data = array();
				foreach ($questions as $question) {
					$questionExists = false;
					foreach ($data as &$item) {
						if ($item['question_id'] === $question->question_id) {
							$item['answers'][] = array(
								'id' => $question->answer_id,
								'answer' => $question->name,
								'value' => $question->value
							);
							$questionExists = true;
							break;
						}
					}
					if (!$questionExists) {
						$data[] = array(
							'question_id' => $question->question_id,
							'question' => $question->question,
							'hasAnswered' => $question->hasAnswered == 1 ? true : false,
							'answers' => array(
								array(
									'id' => $question->answer_id,
									'answer' => $question->name,
									'value' => $question->value
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
}
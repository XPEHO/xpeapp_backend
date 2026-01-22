<?php

namespace XpeApp\qvst\questions;

class GetQuestionsByCampaign {
	public static function apiGetQuestionsByCampaignId(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';

	$params = $request->get_params();

	if (!empty($params)) {
		if (!isset($params['id'])) {
			return new \WP_Error('noID', __('No ID', 'QVST'));
		} else {
			$campaign_id = isset($params['id']) ? intval($params['id']) : 0;

			// Get the campaign with the id (prepared)
			$campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_campaigns} WHERE id = %d", $campaign_id));
			if (empty($campaign)) {
				return new \WP_Error('noID', __('No campaign found', 'QVST'));
			} else {

				// Get the questions of the campaign
				// Use COALESCE to prefer the snapshot text stored in campaign_questions,
				// falling back to current question text for backwards compatibility
				$questionsSql = "
				SELECT
					question.id AS question_id,
					question.text AS question,
					answers.id AS answer_id,
					answers.name,
					answers.value
				FROM
					{$table_name_questions} question
					INNER JOIN {$table_name_campaign_questions} campaigns ON question.id = campaigns.question_id
					INNER JOIN {$table_name_answers_repository} answersRepo ON question.answer_repo_id = answersRepo.id
					INNER JOIN {$table_name_answers} answers ON answers.answer_repo_id = answersRepo.id
				WHERE
					campaigns.campaign_id = %d
				";

			$questions = $wpdb->get_results($wpdb->prepare($questionsSql, $campaign_id));

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
<?php

namespace XpeApp\qvst\questions;

class PostQuestionsAnswers {
	// Todo: This duplicates answers if they already have been made, Fix!
	public static function apiPostQvstAnswers(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_user_answers = $wpdb->prefix . 'qvst_user_answers';
	$table_name_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';

	$params = $request->get_params();
	$body = json_decode($request->get_body());

	// List of parameters (sanitize)
	$campaign_id = isset($params['id']) ? intval($params['id']) : 0;
	$user_id = intval($request->get_header('userId'));
	$token = $request->get_header('Authorization') ?? '';

	if (empty($params) || empty($body)) {
		return new WP_Error('noParams', __('No parameters or body', 'QVST'));
	} else {
		try {
			// Check if the campaign exists (prepared)
			$campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_campaigns} WHERE id = %d", $campaign_id));
			if (empty($campaign)) {
				return new WP_Error('noID', __('No campaign found', 'QVST'));
			}

			// Check if the user exists (prepared)
			$user_table = $wpdb->users;
			$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$user_table} WHERE id = %d", $user_id));
			if (empty($user)) {
				return new WP_Error('noID', __('No user found', 'QVST'));
			}

			foreach ($body as $submittedAnswer) {
				// sanitize submitted ids
				$submitted_question_id = isset($submittedAnswer->questionId) ? intval($submittedAnswer->questionId) : 0;
				$submitted_answer_id = isset($submittedAnswer->answerId) ? intval($submittedAnswer->answerId) : 0;

				// Check if the question exists (prepared)
				$question = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_questions} WHERE id = %d", $submitted_question_id));
				if (empty($question)) {
					return new WP_Error('noID', __('No question found', 'QVST'));
				}

				// Check if the answer exists (prepared)
				$dbAnswer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_answers} WHERE id = %d", $submitted_answer_id));
				if (empty($dbAnswer)) {
					return new WP_Error('noID', __('No answer found', 'QVST'));
				}

				// Save the answer (ensure ints)
				$wpdb->insert(
					$table_name_user_answers,
					array(
						'campaign_id' => $campaign_id,
						'question_id' => intval($question->id),
						'user_id' => $user_id,
						'hasAnswered' => 1
					)
				);
				$wpdb->insert(
					$table_name_campaign_answers,
					array(
						'campaign_id' => $campaign_id,
						'question_id' => intval($question->id),
						'answer_id' => intval($dbAnswer->id),
						'answer_group_id' => (string) $token,
					)
				);
			}

			return new WP_REST_Response(true, 201);
		} catch (\Throwable $th) {
			echo $th;
			return new WP_Error('error', __('Error', 'QVST'));
		}

	}
}
}
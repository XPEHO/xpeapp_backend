<?php

namespace XpeApp\qvst\questions;

class PostQuestionsAnswers {
	// Todo: This duplicates answers if they already have been made, Fix!
	public static function ApiPostQvstAnswers(\WP_REST_Request $request)
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

	// List of parameters
	$campaign_id = $params['id'];
	$user_id = $request->get_header('userId');
	$token = $request->get_header('Authorization');

	if (empty($params) || empty($body)) {
		return new WP_Error('noParams', __('No parameters or body', 'QVST'));
	} else {
		try {
			// Check if the campaign exists
			$campaign = $wpdb->get_row("SELECT * FROM $table_name_campaigns WHERE id=" . $campaign_id);
			if (empty($campaign)) {
				return new WP_Error('noID', __('No campaign found', 'QVST'));
			}

			// Check if the user exists
			$user = $wpdb->get_row("SELECT * FROM wp_users WHERE id=" . $user_id);
			if (empty($user)) {
				return new WP_Error('noID', __('No user found', 'QVST'));
			}

			foreach ($body as $answer) {
				// Check if the question exists
				$question = $wpdb->get_row("SELECT * FROM $table_name_questions WHERE id=" . $answer->questionId);
				if (empty($question)) {
					return new WP_Error('noID', __('No question found', 'QVST'));
				}

				// Check if the answer exists
				$answer = $wpdb->get_row("SELECT * FROM $table_name_answers WHERE id=" . $answer->answerId);
				if (empty($answer)) {
					return new WP_Error('noID', __('No answer found', 'QVST'));
				}

				// Save the answer
				$wpdb->insert(
					$table_name_user_answers,
					array(
						'campaign_id' => $campaign_id,
						'question_id' => $question->id,
						'user_id' => $user_id,
						'hasAnswered' => 1
					)
				);
				$wpdb->insert(
					$table_name_campaign_answers,
					array(
						'campaign_id' => $campaign_id,
						'question_id' => $question->id,
						'answer_id' => $answer->id,
						'answer_group_id' => $token,
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
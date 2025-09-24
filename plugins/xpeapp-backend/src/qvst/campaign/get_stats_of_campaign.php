<?php

require_once __DIR__ . '/campaign_themes_utils.php';

function api_get_qvst_stats_by_campaign_id(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';

	$params = $request->get_params();

	$campaign_id = $params['id'];

	if (empty($campaign_id)) {
		return new WP_Error('noParams', __('No parameters', 'QVST'));
	}

	try {
		// Get the campaign with the id
		$campaign = $wpdb->get_row("SELECT * FROM $table_name_campaigns WHERE id=" . $campaign_id);
		if (empty($campaign)) {
			return new WP_Error('noID', __('No campaign found', 'QVST'));
		}
		// Get the questions of the campaign 
		$questionsSql = "
			SELECT 
				questions.id as 'question_id',
				questions.text as 'question',
				questions.answer_repo_id,
				campaign.status as 'status',
				campaign.action as 'action' 
			FROM $table_name_questions questions
			INNER JOIN $table_name_campaign_questions c_q ON questions.id=c_q.question_id
			INNER JOIN $table_name_campaigns campaign ON c_q.campaign_id=campaign.id
			WHERE campaign.id=$campaign_id";

		$questions = $wpdb->get_results($questionsSql);

		// Récupérer les thèmes de la campagne
		$themes = getThemesForCampaign($campaign_id);

		// Get the answers of each question
		foreach ($questions as &$question) {
			$answersSql = "
					SELECT 
					answers.id,
					answers.name as 'answer',
					answers.value,
					COUNT(campaigns_answers.id) as 'numberAnswered'
				FROM $table_name_answers answers
				INNER JOIN $table_name_campaign_answers campaigns_answers ON answers.id=campaigns_answers.answer_id
				WHERE campaigns_answers.campaign_id=$campaign_id AND campaigns_answers.question_id=$question->question_id
				GROUP BY answers.id
			";

			$answers = $wpdb->get_results($answersSql);

			$question->answers = $answers;

			// Parse numberAnswered to int
			foreach ($question->answers as &$answer) {
				$answer->numberAnswered = (int) $answer->numberAnswered;
			}

			$answersSql = "
					SELECT 
					answers.id,
					answers.name as 'answer',
					answers.value,
					0 as 'numberAnswered' 
				FROM $table_name_answers answers
				WHERE answers.answer_repo_id=$question->answer_repo_id AND answers.id NOT IN (
					SELECT campaigns_answers.answer_id
					FROM $table_name_campaign_answers campaigns_answers
					WHERE campaigns_answers.campaign_id=$campaign_id AND campaigns_answers.question_id=$question->question_id
				)
			";

			$answers = $wpdb->get_results($answersSql);

			$question->answers = array_merge($question->answers, $answers);

			// Parse numberAnswered to int
			foreach ($question->answers as &$answer) {
				$answer->numberAnswered = (int) $answer->numberAnswered;
			}
		}

		$data = array(
			'campaignId' => $campaign_id,
			'campaignName' => $campaign->name,
			'campaignStatus' => $campaign->status,
			'startDate' => $campaign->start_date,
			'endDate' => $campaign->end_date,
			'action' => $campaign->action,
			'themes' => $themes,
			'questions' => $questions
		);


		// Return 200 OK status code if success with datas
		return new WP_REST_Response($data, 200);
	} catch (\Throwable $th) {
		xpeapp_log(Xpeapp_Log_Level::Error, "");
		return new WP_Error('error', __('Error', 'QVST'));
	}
}
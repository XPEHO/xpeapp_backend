<?php
namespace XpeApp\qvst\campaign;

require_once __DIR__ . '/campaign_themes_utils.php';

class GetStatsOfCampaign {
	public static function apiGetQvstStatsByCampaignId(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	/** @var wpdb $wpdb */
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
	$table_name_questions = $wpdb->prefix . 'qvst_questions';
	$table_name_answers = $wpdb->prefix . 'qvst_answers';
	$table_name_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';

	$params = $request->get_params();

	$campaign_id = isset($params['id']) ? intval($params['id']) : 0;

	if (empty($campaign_id)) {
		return new \WP_Error('noParams', __('No parameters', 'QVST'));
	}

	try {
		// Get the campaign with the id (prepared)
		$campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name_campaigns} WHERE id = %d", $campaign_id));
		if (empty($campaign)) {
			return new \WP_Error('noID', __('No campaign found', 'QVST'));
		}
		// Get the questions of the campaign
		$questionsSql = "
			SELECT
				questions.id as question_id,
				questions.text as question,
				questions.answer_repo_id,
				campaign.status as status,
				campaign.action as action
			FROM {$table_name_questions} questions
			INNER JOIN {$table_name_campaign_questions} c_q ON questions.id = c_q.question_id
			INNER JOIN {$table_name_campaigns} campaign ON c_q.campaign_id = campaign.id
			WHERE campaign.id = %d";

		$questions = $wpdb->get_results($wpdb->prepare($questionsSql, $campaign_id));

		// Récupérer les thèmes de la campagne
		$themes = getThemesForCampaign($campaign_id);

		// Get the answers of each question
		foreach ($questions as &$question) {
			$question_id = intval($question->question_id);
			$answersSql = "
				SELECT
					answers.id,
					answers.name as answer,
					answers.value,
					COUNT(campaigns_answers.id) as numberAnswered
				FROM {$table_name_answers} answers
				INNER JOIN {$table_name_campaign_answers} campaigns_answers ON answers.id = campaigns_answers.answer_id
				WHERE campaigns_answers.campaign_id = %d AND campaigns_answers.question_id = %d
				GROUP BY answers.id
			";

			$answers = $wpdb->get_results($wpdb->prepare($answersSql, $campaign_id, $question_id));

			$question->answers = $answers;

			// Parse numberAnswered to int
			foreach ($question->answers as &$answer) {
				$answer->numberAnswered = (int) $answer->numberAnswered;
			}
			unset($answer);

			$answer_repo_id = intval($question->answer_repo_id);
			$answersSql = "
				SELECT
					answers.id,
					answers.name as answer,
					answers.value,
					0 as numberAnswered
				FROM {$table_name_answers} answers
				WHERE answers.answer_repo_id = %d AND answers.id NOT IN (
					SELECT campaigns_answers.answer_id
					FROM {$table_name_campaign_answers} campaigns_answers
					WHERE campaigns_answers.campaign_id = %d AND campaigns_answers.question_id = %d
				)
			";

			$answers = $wpdb->get_results($wpdb->prepare($answersSql, $answer_repo_id, $campaign_id, $question_id));

			$question->answers = array_merge($question->answers, $answers);

			// Parse numberAnswered to int
			foreach ($question->answers as &$answer) {
				$answer->numberAnswered = (int) $answer->numberAnswered;
			}
			unset($answer);
		}
		unset($question);

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
		return new \WP_REST_Response($data, 200);
	} catch (\Throwable $th) {
		xpeapp_log(Xpeapp_Log_Level::Error, "");
		return new \WP_Error('error', __('Error', 'QVST'));
	}
}
}
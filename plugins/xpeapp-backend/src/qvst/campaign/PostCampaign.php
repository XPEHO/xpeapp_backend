<?php

namespace XpeApp\qvst\campaign;
require_once __DIR__ . '/campaign_themes_utils.php';

class PostCampaign {
	public static function apiPostCampaign(\WP_REST_Request $request)
	{
		xpeapp_log_request($request);
		global $wpdb;
		$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
		$table_name_theme = $wpdb->prefix . 'qvst_theme';
		$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
		$table_name_questions = $wpdb->prefix . 'qvst_questions';
		$params = $request->get_params();

		$response = null;

		$validation_error = self::validateCampaignParams($params);
		if ($validation_error) {
			$response = $validation_error;
		} else {
			$theme_error = self::validateThemesExist($params['themes'], $table_name_theme, $wpdb);
			if ($theme_error) {
				$response = $theme_error;
			} else {
				try {
					$campaignToInsert = array(
						'name' => $params['name'],
						'status' => 'DRAFT',
						'start_date' => $params['start_date'],
						'end_date' => $params['end_date']
					);

					$wpdb->insert($table_name_campaigns, $campaignToInsert);
					$campaign_id = $wpdb->insert_id;
					setThemesForCampaign($campaign_id, $params['themes']);

					self::insertCampaignQuestions($params['questions'], $campaign_id, $table_name_questions, $table_name_campaign_questions, $wpdb);

					$response = new \WP_REST_Response(null, 201);
				} catch (\Throwable $th) {
					$response = new \WP_Error('error', __('Error', 'QVST'));
				}
			}
		}
		return $response;
	}

	private static function validateCampaignParams($params)
	{
		$error = null;
		if (empty($params)) {
			$error = new \WP_Error('noParams', __('No parameters', 'QVST'));
		} elseif (!isset($params['name'])) {
			$error = new \WP_Error('noName', __('No name', 'QVST'));
		} elseif (!isset($params['themes']) || !is_array($params['themes']) || count($params['themes']) === 0) {
			$error = new \WP_Error('noThemes', __('No themes array provided', 'QVST'));
		} elseif (!isset($params['start_date'])) {
			$error = new \WP_Error('noStartDate', __('No start date', 'QVST'));
		} elseif (!isset($params['end_date'])) {
			$error = new \WP_Error('noEndDate', __('No end date', 'QVST'));
		} elseif (!isset($params['questions'])) {
			$error = new \WP_Error('noQuestions', __('No questions', 'QVST'));
		}
		return $error;
	}

	private static function validateThemesExist($themes, $table_name_theme, $wpdb)
	{
		foreach ($themes as $theme_id) {
			$theme = $wpdb->get_row("SELECT * FROM $table_name_theme WHERE id=" . intval($theme_id));
			if (empty($theme)) {
				return new \WP_Error('noID', __('No theme found for id ' . $theme_id, 'QVST'));
			}
		}
		return null;
	}

	private static function insertCampaignQuestions($questions, $campaign_id, $table_name_questions, $table_name_campaign_questions, $wpdb)
	{
		foreach ($questions as $question) {
			$question_data = $wpdb->get_row($wpdb->prepare(
				"SELECT no_longer_used FROM $table_name_questions WHERE id = %d",
				$question['id']
			));
			if ($question_data && $question_data->no_longer_used == 1) {
				xpeapp_log(\Xpeapp_Log_Level::Warn, "POST campaign - Skipping question ID {$question['id']} (no_longer_used)");
				continue;
			}
			$wpdb->insert(
				$table_name_campaign_questions,
				array(
					'campaign_id' => $campaign_id,
					'question_id' => $question['id']
				)
			);
		}
	}
}
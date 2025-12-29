<?php

namespace XpeApp\qvst\campaign;
require_once __DIR__ . '/campaign_themes_utils.php';

class PostCampaign {
	public static function apiPostCampaign(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

	$params = $request->get_params();

	// Validation des paramètres
	$validation_error = null;
	if (empty($params)) {
		$validation_error = new \WP_Error('noParams', __('No parameters', 'QVST'));
	} elseif (!isset($params['name'])) {
		$validation_error = new \WP_Error('noName', __('No name', 'QVST'));
	} elseif (!isset($params['themes']) || !is_array($params['themes']) || count($params['themes']) === 0) {
		$validation_error = new \WP_Error('noThemes', __('No themes array provided', 'QVST'));
	} elseif (!isset($params['start_date'])) {
		$validation_error = new \WP_Error('noStartDate', __('No start date', 'QVST'));
	} elseif (!isset($params['end_date'])) {
		$validation_error = new \WP_Error('noEndDate', __('No end date', 'QVST'));
	} elseif (!isset($params['questions'])) {
		$validation_error = new \WP_Error('noQuestions', __('No questions', 'QVST'));
	}

	try {
		// Vérifier que tous les thèmes existent si pas d'erreur de validation
		if (!$validation_error) {
			foreach ($params['themes'] as $theme_id) {
				$theme = $wpdb->get_row("SELECT * FROM $table_name_theme WHERE id=" . intval($theme_id));
				if (empty($theme)) {
					$validation_error = new \WP_Error('noID', __('No theme found for id ' . $theme_id, 'QVST'));
					break;
				}
			}
		}

		if ($validation_error) {
			return $validation_error;
		}

		$campaignToInsert = array(
			'name' => $params['name'],
			'status' => 'DRAFT',
			'start_date' => $params['start_date'],
			'end_date' => $params['end_date']
		);

		// save campaign
		$wpdb->insert(
			$table_name_campaigns,
			$campaignToInsert,
		);

		// get campaign id
		$campaign_id = $wpdb->insert_id;

		// Associer les thèmes à la campagne
		setThemesForCampaign($campaign_id, $params['themes']);

		// save questions
		foreach ($params['questions'] as $question) {
			$wpdb->insert(
				$table_name_campaign_questions,
				array(
					'campaign_id' => $campaign_id,
					'question_id' => $question['id']
				)
			);
		}

		// return 201 created status code if success
		return new \WP_REST_Response(null, 201);
	} catch (\Throwable $th) {
		return new \WP_Error('error', __('Error', 'QVST'));
	}
}
}
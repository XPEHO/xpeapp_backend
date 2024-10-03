<?php


function api_post_campaign(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	
	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	// Nom de la table personnalisée
	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';
	$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

	$params = $request->get_params();

	if (empty($params)) {
		return new WP_Error('noParams', __('No parameters', 'QVST'));

	} else if (!isset($params['name'])) {
		return new WP_Error('noName', __('No name', 'QVST'));

	} else if (!isset($params['theme_id'])) {
		return new WP_Error('noThemeID', __('No theme ID', 'QVST'));

	} else if (!isset($params['start_date'])) {
		return new WP_Error('noStartDate', __('No start date', 'QVST'));

	} else if (!isset($params['end_date'])) {
		return new WP_Error('noEndDate', __('No end date', 'QVST'));

	} else if (!isset($params['questions'])) {
		return new WP_Error('noQuestions', __('No questions', 'QVST'));
	} else {

		try {
			// get theme with id
			$theme = $wpdb->get_row("SELECT * FROM $table_name_theme WHERE id=" . $params['theme_id']);
			if (empty($theme)) {
				return new WP_Error('noID', __('No theme found', 'QVST'));
			}

			$campaignToInsert = array(
				'name' => $params['name'],
				'theme_id' => $params['theme_id'],
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
			return new WP_REST_Response(null, 201);
		} catch (\Throwable $th) {
			return new WP_Error('error', __('Error', 'QVST'));
		}
	}
}
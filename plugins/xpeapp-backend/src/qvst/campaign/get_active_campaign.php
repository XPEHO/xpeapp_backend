<?php

function get_open_campaign(WP_REST_Request $request)
{
	xpeapp_log_request($request);

	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
	$table_name_theme = $wpdb->prefix . 'qvst_theme';

	$queryCampaigns = "
		SELECT 
			campaign.id,
			campaign.name,
			theme.id as 'theme_id',
			campaign.status,
			campaign.start_date,
			campaign.end_date,
			campaign.action,
			-- Calcul du taux de participation
			(COUNT(DISTINCT CASE WHEN user_answers.hasAnswered = 1 THEN user_answers.user_id END) / COUNT(DISTINCT users.id)) * 100 AS participation_rate
		FROM $table_name_campaigns campaign
		INNER JOIN wp_qvst_theme theme ON campaign.theme_id = theme.id
		LEFT JOIN wp_qvst_user_answers user_answers ON user_answers.campaign_id = campaign.id
		CROSS JOIN wp_users users
		WHERE campaign.status = 'OPEN'
		GROUP BY
			campaign.id,
			campaign.name,
			theme.id,
			campaign.status,
			campaign.start_date,
			campaign.end_date,
			campaign.action;
	";

	$resultsCampaigns = $wpdb->get_results($queryCampaigns);

	// Return all rows
	$data = array();
	foreach ($resultsCampaigns as $result) {
		$theme = $wpdb->get_row("SELECT * FROM $table_name_theme WHERE id=" . $result->theme_id);
		$data[] = array(
			'id' => $result->id,
			'name' => $result->name,
			'theme' => $theme,
			'status' => $result->status,
			'start_date' => $result->start_date,
			'end_date' => $result->end_date,
			'action' => $result->action,
			'participation_rate' => $result->participation_rate,
		);
	}
	// return $resultsCampaigns in json format
	return $data;

}
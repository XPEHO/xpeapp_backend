<?php
namespace XpeApp\qvst\campaign;

require_once __DIR__ . '/campaign_themes_utils.php';

class GetListOfCampaigns {
	// Format results with associated themes
	// Build query for all campaigns (no filter)
	public static function apiGetCampaigns(\WP_REST_Request $request)
	{
		xpeapp_log_request($request);
		global $wpdb;

		$queryCampaigns = buildCampaignsQuery();
		$resultsCampaigns = $wpdb->get_results($queryCampaigns);

		return formatCampaignResults($resultsCampaigns);
	}
}

// Build SQL query to fetch campaigns with participation rate
function buildCampaignsQuery($whereClause = '') {
	global $wpdb;
	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';
   
	$query = "
		SELECT 
			campaign.id,
			campaign.name,
			campaign.status,
			campaign.start_date,
			campaign.end_date,
			campaign.action,
			-- Calcul du taux de participation
			(COUNT(DISTINCT CASE WHEN user_answers.hasAnswered = 1 THEN user_answers.user_id END) / COUNT(DISTINCT users.id)) * 100 AS participation_rate
		FROM $table_name_campaigns campaign
		LEFT JOIN wp_qvst_user_answers user_answers ON user_answers.campaign_id = campaign.id
		CROSS JOIN wp_users users";
   
	if ($whereClause) {
		$query .= " WHERE $whereClause";
	}
   
	$query .= " GROUP BY
		campaign.id,
		campaign.name,
		campaign.status,
		campaign.start_date,
		campaign.end_date,
		campaign.action";
   
	return $query;
}

// Format campaign results with associated themes
function formatCampaignResults($resultsCampaigns) {
	$data = array();
	foreach ($resultsCampaigns as $result) {
		$themes = getThemesForCampaign($result->id);
		$data[] = array(
			'id' => $result->id,
			'name' => $result->name,
			'themes' => $themes,
			'status' => $result->status,
			'start_date' => $result->start_date,
			'end_date' => $result->end_date,
			'action' => $result->action,
			'participation_rate' => $result->participation_rate,
		);
	}
	return $data;
}
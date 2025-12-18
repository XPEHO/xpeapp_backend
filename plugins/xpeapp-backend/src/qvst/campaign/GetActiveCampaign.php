<?php

namespace XpeApp\qvst\campaign;
include_once __DIR__ . '/GetListOfCampaigns.php';
include_once __DIR__ . '/../../logging.php';


class GetActiveCampaign {
	// Fetch campaigns with status 'OPEN'
	public static function getOpenCampaign(\WP_REST_Request $request)
{
	xpeapp_log_request($request);
	global $wpdb;

	$queryCampaigns = buildCampaignsQuery("campaign.status = 'OPEN'");
	$resultsCampaigns = $wpdb->get_results($queryCampaigns);

	return formatCampaignResults($resultsCampaigns);
}
}
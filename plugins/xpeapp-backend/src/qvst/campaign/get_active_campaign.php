<?php
require_once __DIR__ . '/campaign_themes_utils.php';
require_once __DIR__ . '/get_list_of_campaigns.php';

// Fetch campaigns with status 'OPEN'
function get_open_campaign(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	global $wpdb;

	$queryCampaigns = buildCampaignsQuery("campaign.status = 'OPEN'");
	$resultsCampaigns = $wpdb->get_results($queryCampaigns);

	return formatCampaignResults($resultsCampaigns);
}
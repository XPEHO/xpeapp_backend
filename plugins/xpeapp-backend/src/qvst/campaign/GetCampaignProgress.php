<?php

namespace XpeApp\qvst\campaign;

class GetCampaignProgress {
	public static function ApiGetCampaignProgress(\WP_REST_Request $request) {
	xpeapp_log_request($request);

	$user_id = $request->get_param('userId');
	$campaign_id = $request->get_param('campaignId');

	/** @var wpdb $wpdb */
	global $wpdb;

	$query_params = [];
	$query = 
	"with
		tq as (
			SELECT
				campaign_id,
				COUNT(1) as total_questions
			FROM
				wp_qvst_campaign_questions
			group by
				campaign_id
		)
	select
		ua.user_id,
		ua.campaign_id,
		count(1) as answered_questions,
		tq.total_questions
	from
		wp_qvst_user_answers ua
		left join tq on tq.campaign_id = ua.campaign_id
	group by
		user_id,
		campaign_id
	having 1";

	if($user_id) {
		$query = $query . " and user_id=%d";
		$query_params[] = $user_id;
	}
	if($campaign_id) {
		$query = $query . " and campaign_id=%d";
		$query_params[] = $campaign_id;
	}

	$query = $wpdb->prepare($query, ...$query_params);

	$res = $wpdb->get_results($query);

	return $res;
}
}
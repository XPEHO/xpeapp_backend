<?php

namespace XpeApp\qvst\campaign;

class PutCampaignStatus {
	public static function api_update_campaign_status(\WP_REST_Request $request)
{
	xpeapp_log_request($request);

	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';

	$params = $request->get_params();
	$body = json_decode($request->get_body());

	if (empty($params) || empty($body)) {
		return new \WP_Error('noParams', __('No parameters or body', 'QVST'));
	}

	try {
		// Check if the campaign exists
		$campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_campaigns WHERE id = %d", $params['id']));
		if (empty($campaign)) {
			return new \WP_Error('noID', __('No campaign found', 'QVST'));
		}

		// Store old status to detect DRAFT -> OPEN transition
		$old_status = $campaign->status;
		
		// Update status of the campaign with status in the body and action if the status is 'CLOSED'
		$update_data = array('status' => $body->status);
		if ($body->status == 'ARCHIVED') {
			$update_data['action'] = $body->action;
		}
		
		$wpdb->update(
			$table_name_campaigns,
			$update_data,
			array('id' => $params['id'])
		);
		
		// Send notification when campaign moves from DRAFT to OPEN
		if ($old_status === 'DRAFT' && $body->status === 'OPEN') {
			// Immediate notification
			sendFcmNotification(
				'Nouvelle campagne QVST !',
				'Donnez votre avis dans la nouvelle campagne QVST !'
			);
			xpeapp_log(Xpeapp_Log_Level::Info, "Sent notification for campaign opening: {$campaign->name}");
			
			// Schedule reminder in 7 days
			wp_schedule_single_event(
				time() + (7 * DAY_IN_SECONDS),
				'xpeapp_campaign_reminder',
				array($params['id'])
			);
			xpeapp_log(Xpeapp_Log_Level::Info, "Scheduled reminder in 7 days for campaign: {$campaign->name}");
		}

		return new \WP_REST_Response(null, 201);

	} catch (\Throwable $th) {
		echo $th;
		return new \WP_Error('error', __('Error', 'QVST'));
	}
}
}
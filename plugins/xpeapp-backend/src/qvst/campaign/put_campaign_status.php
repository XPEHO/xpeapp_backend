<?php

function api_update_campaign_status(WP_REST_Request $request)
{
	xpeapp_log_request($request);

	// Utiliser la classe $wpdb pour effectuer une requête SQL
	global $wpdb;

	$table_name_campaigns = $wpdb->prefix . 'qvst_campaign';

	$params = $request->get_params();
	$body = json_decode($request->get_body());

	if (empty($params) || empty($body)) {
		return new WP_Error('noParams', __('No parameters or body', 'QVST'));
	} else {
		try {

			// Check if the campaign exists
			$campaign = $wpdb->get_row("SELECT * FROM $table_name_campaigns WHERE id=" . $params['id']);
			if (empty($campaign)) {
				return new WP_Error('noID', __('No campaign found', 'QVST'));
			} else {
				// Update status of the campaign with status in the body and action if the status is 'CLOSED'
				if ($body->status == 'ARCHIVED') {
					$wpdb->update(
						$table_name_campaigns,
						array(
							'status' => $body->status,
							'action' => $body->action
						),
						array(
							'id' => $params['id']
						)
					);
				} else {
					$wpdb->update(
						$table_name_campaigns,
						array(
							'status' => $body->status
						),
						array(
							'id' => $params['id']
						)
					);
				}
			}

			return new WP_REST_Response(null, 201);

		} catch (\Throwable $th) {
			echo $th;
			return new WP_Error('error', __('Error', 'QVST'));
		}
	}
}
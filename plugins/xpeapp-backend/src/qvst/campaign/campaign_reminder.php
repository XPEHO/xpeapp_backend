<?php

/**
 * Envoyer une notification de rappel pour une campagne
 */
function sendCampaignReminder($campaign_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'qvst_campaign';

	$campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $campaign_id));

	if ($campaign && $campaign->status === 'OPEN') {
		sendFcmNotification(
			'Rappel campagne QVST !',
			'Dernier jour, n\'oubliez pas de donner votre avis dans la campagne QVST !'
		);
		xpeapp_log(Xpeapp_Log_Level::Info, "Sent reminder notification for campaign: {$campaign->name}");
	} else {
		xpeapp_log(Xpeapp_Log_Level::Info, "Campaign $campaign_id not found or not OPEN, skipping reminder");
	}
}

// Hook pour l'événement programmé
add_action('xpeapp_campaign_reminder', 'sendCampaignReminder');

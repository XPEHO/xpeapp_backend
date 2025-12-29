<?php
namespace XpeApp\qvst\campaign;
/**
 * Envoyer une notification de rappel pour une campagne
 */
include_once __DIR__ . '/../../logging.php';
include_once __DIR__ . '/../../notification/notification_helpers.php';

class CampaignReminder {
	public static function sendCampaignReminder($campaign_id)
{
	global $wpdb;

    $table_name = $wpdb->prefix . 'qvst_campaign';


    $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $campaign_id));

    if ($campaign && $campaign->status === 'OPEN') {
        sendFcmNotification(
            'Rappel campagne QVST !',
            'Dernier jour, n\'oubliez pas de donner votre avis dans la campagne QVST !'
        );
        xpeapp_log(\Xpeapp_Log_Level::Info, "Sent reminder notification for campaign: {$campaign->name}");
    } else {
        xpeapp_log(\Xpeapp_Log_Level::Info, "Campaign $campaign_id not found or not OPEN, skipping reminder");
    }
}
}

// Hook pour l'événement programmé
add_action('xpeapp_CampaignReminder', [CampaignReminder::class, 'sendCampaignReminder']);

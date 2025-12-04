<?php

/**
 * Send a notification via the internal notifications endpoint
 * 
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $redirection Optional redirection action (default: OPEN_XPEAPP)
 * @return bool True if notification sent successfully, false otherwise
 */
function send_fcm_notification($title, $message, $redirection = 'OPEN_XPEAPP')
{
	// Prepare notification data
	$notification_data = buildServiceAccountData();
	
	if (!$notification_data) {
		xpeapp_log(Xpeapp_Log_Level::Error, 'Cannot send notification: Firebase credentials missing');
		return false;
	}

	try {
		$messaging = (new \Kreait\Firebase\Factory)
			->withServiceAccount($notification_data)
			->createMessaging();

		$cloudMessage = \Kreait\Firebase\Messaging\CloudMessage::fromArray([
			'topic' => 'all',
			'notification' => [
				'title' => $title,
				'body' => $message,
			],
			'android' => [
				'notification' => [
					'click_action' => $redirection,
				],
			],
		]);

		$messaging->send($cloudMessage);

		xpeapp_insert_fcm_send([
			'title' => $title,
			'message' => $message,
			'status' => 'sent',
		]);

		xpeapp_log(Xpeapp_Log_Level::Info, "Notification sent: $title");
		return true;
	} catch (\Throwable $e) {
		xpeapp_log(Xpeapp_Log_Level::Error, 'FCM send failed: ' . $e->getMessage());
		xpeapp_insert_fcm_send([
			'title' => $title,
			'message' => $message,
			'status' => 'failed',
			'error' => $e->getMessage(),
		]);
		return false;
	}
}

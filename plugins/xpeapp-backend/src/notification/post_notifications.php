<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

include_once __DIR__ . '/../utils.php';
include_once __DIR__ . '/fcm_log.php';

function api_post_notification(WP_REST_Request $request)
{
	xpeapp_log_request($request);

	$params = $request->get_json_params();

	// Validate required parameters
	$validation_error = validateParams($params, ['title', 'message']);
	if ($validation_error) {
		logNotificationAttempt($params, 'validation_failed', 'missing_required_fields');
		return $validation_error;
	}

	// Prepare notification data
	$title = trim($params['title']);
	$message = trim($params['message']);
	$redirection = trim($params['redirection'] ?? 'OPEN_XPEAPP');
	$topic = 'all';

	// Build Firebase service account from environment variables
	$serviceAccountData = buildServiceAccountData();
	if (!$serviceAccountData) {
		logNotificationAttempt($params, 'failed', 'firebase_credentials_missing');
		return createErrorResponse('firebase_credentials_missing', 'Firebase credentials not configured', 500);
	}

	// Send notification via FCM
	try {
		$messaging = (new Factory)
			->withServiceAccount($serviceAccountData)
			->createMessaging();

		$cloudMessage = CloudMessage::fromArray([
			'topic' => $topic,
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

		return createSuccessResponse(null, 200);
	} catch (\Throwable $e) {
		xpeapp_log(Xpeapp_Log_Level::Error, 'FCM send failed: ' . $e->getMessage());
		logNotificationAttempt($params, 'failed', $e->getMessage());
		return createErrorResponse('notification_send_failed', 'Notification send failed', 500);
	}
}

function buildServiceAccountData()
{
	$serviceAccountData = [
		'type' => getenv('TYPE') ?: 'service_account',
		'project_id' => getenv('PROJECT_ID') ?: '',
		'private_key_id' => getenv('PRIVATE_KEY_ID') ?: '',
		'private_key' => getenv('PRIVATE_KEY') ?: '',
		'client_email' => getenv('CLIENT_EMAIL') ?: '',
		'client_id' => getenv('CLIENT_ID') ?: '',
		'auth_uri' => getenv('AUTH_URI') ?: 'https://accounts.google.com/o/oauth2/auth',
		'token_uri' => getenv('TOKEN_URI') ?: 'https://oauth2.googleapis.com/token',
		'auth_provider_x509_cert_url' => getenv('AUTH_PROVIDER_X509_CERT_URL') ?: 'https://www.googleapis.com/oauth2/v1/certs',
		'client_x509_cert_url' => getenv('CLIENT_X509_CERT_URL') ?: '',
	];

	// Check required fields
	$required = ['project_id', 'private_key', 'client_email'];
	foreach ($required as $field) {
		if (empty($serviceAccountData[$field])) {
			return null;
		}
	}

	// Normalize private key newlines
	$serviceAccountData['private_key'] = str_replace(["\r\n", "\\n"], "\n", $serviceAccountData['private_key']);

	return $serviceAccountData;
}

function logNotificationAttempt($params, $status, $error = null)
{
	xpeapp_insert_fcm_send([
		'title' => $params['title'] ?? '',
		'message' => $params['message'] ?? '',
		'status' => $status,
		'error' => $error,
	]);
}
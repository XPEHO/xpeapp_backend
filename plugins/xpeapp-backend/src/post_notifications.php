<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

function api_post_notification(WP_REST_Request $request)
{
	xpeapp_log_request($request);
	$googleServiceFile = "/var/www/html/wp-content/themes/twentytwentytwo/assets/google_services.json";

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// Récupérez le corps de la requête POST
		$postData = file_get_contents('php://input');

		// Convertissez les données JSON en tableau associatif
		$jsonData = json_decode($postData, true);

		// Vérifiez si le décodage JSON a réussi
		if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
			xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/notifications failed, invalid json body");
			return new WP_REST_Response(array(
				"error" => "Invalid JSON payload",
				"message" => "The request body is not a valid JSON format."
			  ), 400);
		}

		$titleOfNotification = $jsonData['title'];
		$message = $jsonData['message'];
		$redirection = $jsonData['redirection'];
	}

	// Vérifiez si le fichier JSON existe
	if (!file_exists($googleServiceFile)) {
		xpeapp_log(Xpeapp_Log_Level::Error, "Missing the google_services.json Google Service File that contains Firebase secret keys.");
		return new WP_REST_Response(array(
				"error" => "Internal Server Error",
				"message" => "xpeho/v1/notifications is incorrecly configured."
			), 500);
	}

	// Lire le contenu du fichier JSON
	$jsonContent = file_get_contents($googleServiceFile);

	// Décoder le contenu JSON en tableau associatif
	$serviceAccountData = json_decode($jsonContent, true);

	// Vérifiez si le décodage JSON a réussi
	if (json_last_error() !== JSON_ERROR_NONE) {
		xpeapp_log(Xpeapp_Log_Level::Warn, "POST xpeho/v1/notifications failed, invalid json body");
		return new WP_REST_Response(array(
			"error" => "Invalid JSON payload",
			"message" => "The request body is not a valid JSON format. " . json_last_error_msg()
		  ), 400);
	}

	// Créer un objet ServiceAccount à partir du tableau associatif
	$firebase = (new Factory)
		->withServiceAccount($serviceAccountData)
		->createMessaging();

	// Send to a topic
	$topic = 'newsletter';

	// See documentation on defining a message payload.
	$message = CloudMessage::fromArray([
		'topic' => $topic,
		'notification' => [
			'title' => $titleOfNotification,
			'body' => $message,
		],
		'android' => [
			'notification' => [
				'click_action' => $redirection,
			],
		],
	]);

	// Send a message to the device corresponding to the provided
// registration token.
	$firebase->send($message);

	return new WP_REST_Response(null, 201);
}
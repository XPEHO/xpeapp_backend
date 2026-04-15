<?php

include_once __DIR__ . '/../utils.php';

function apiSubscribeAdminWebIdeaNotifications(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    $params = $request->get_json_params();
    $validation_error = validateParams($params, ['token']);
    if ($validation_error) {
        $response = $validation_error;
    } else {
        $token = trim((string) $params['token']);
        $app = trim((string) ($params['app'] ?? 'admin'));
        $platform = trim((string) ($params['platform'] ?? 'web'));

        if ($token === '') {
            $response = createErrorResponse('invalid_token', 'FCM token is required', 400);
        } elseif ($app !== 'admin' || $platform !== 'web') {
            $response = createErrorResponse('invalid_target', 'Only admin web subscriptions are allowed', 400);
        } else {
            $serviceAccountData = buildServiceAccountData();
            if (!$serviceAccountData) {
                $response = createErrorResponse('firebase_credentials_missing', 'Firebase credentials not configured', 500);
            } else {
                try {
                    $topic = 'admin_web_ideas';
                    $messaging = (new \Kreait\Firebase\Factory)
                        ->withServiceAccount($serviceAccountData)
                        ->createMessaging();

                    $messaging->subscribeToTopic($topic, [$token]);

                    xpeapp_log(Xpeapp_Log_Level::Info, 'Subscribed admin web token to topic admin_web_ideas');
                    $response = createSuccessResponse(['topic' => $topic], 200);
                } catch (\Throwable $e) {
                    xpeapp_log(Xpeapp_Log_Level::Error, 'Admin web topic subscription failed: ' . $e->getMessage());
                    $response = createErrorResponse('subscription_failed', 'Could not subscribe token to topic', 500);
                }
            }
        }
    }

    return $response;
}
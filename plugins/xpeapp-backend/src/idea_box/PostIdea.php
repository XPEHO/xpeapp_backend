<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';
include_once __DIR__ . '/../notification/notification_helpers.php';

class PostIdea {
    private const IDEA_NOTIFICATION_TOPIC = 'admin_web_ideas';

    public static function apiPostIdea(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;
    $table_idea_box = $wpdb->prefix . 'idea_box';

    $params = $request->get_params();

    // Check if the parameters are valid
    $validation_error = validateParams($params, ['context', 'description']);
    if ($validation_error) {
        $response = $validation_error;
    } else {
        // Prepare data for insertion (context, description, optional user_id)
        $user_id = get_current_user_id();
        $data = [
            'context' => sanitize_text_field($params['context']),
            'description' => sanitize_textarea_field($params['description']),
            'user_id' => $user_id > 0 ? intval($user_id) : null
        ];

        // Insert the idea into the database
        $result = $wpdb->insert($table_idea_box, $data);

        if ($result === false) {
            $response = createErrorResponse('db_insert_error', 'Could not insert idea', 500);
        } else {
            $notificationSent = \sendFcmNotification(
                'Nouvelle idée soumise',
                'Une nouvelle idée a été ajoutée dans la boîte à idées.',
                'OPEN_XPEAPP',
                self::IDEA_NOTIFICATION_TOPIC
            );

            if (!$notificationSent) {
                \xpeapp_log(\Xpeapp_Log_Level::Warn, 'Idea created but notification failed to send');
            }

            $response = createSuccessResponse(null, 201);
        }
    }

    return $response;
}
}

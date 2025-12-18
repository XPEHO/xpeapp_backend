<?php
namespace XpeApp\idea_box;

use WP_REST_Request;

include_once __DIR__ . '/../utils.php';

class PostIdea {
    public static function ApiPostIdea(WP_REST_Request $request)
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
        // Prepare data for insertion (only context and description)
        $data = [
            'context' => sanitize_text_field($params['context']),
            'description' => sanitize_textarea_field($params['description'])
        ];

        // Insert the idea into the database
        $result = $wpdb->insert($table_idea_box, $data);

        if ($result === false) {
            $response = createErrorResponse('db_insert_error', 'Could not insert idea', 500);
        } else {
            $response = createSuccessResponse(null, 201);
        }
    }

    return $response;
}
}

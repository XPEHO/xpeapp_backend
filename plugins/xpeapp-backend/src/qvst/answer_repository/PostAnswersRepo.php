<?php

namespace XpeApp\qvst\answer_repository;

class PostAnswersRepo {
    public static function apiPostAnswersRepo(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        global $wpdb;
        $table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';
        $table_name_answers = $wpdb->prefix . 'qvst_answers';
        $body = json_decode($request->get_body());

        // Validation
        if (empty($body)) {
            $response = createErrorResponse('missing_body', 'No body provided', 400);
        } elseif (!isset($body->repoName) || empty($body->repoName)) {
            $response = createErrorResponse('missing_param', 'Missing repoName', 400);
        } elseif (!isset($body->answers) || empty($body->answers) || !is_array($body->answers)) {
            $response = createErrorResponse('missing_param', 'Missing or invalid answers array', 400);
        } elseif (entityExists($body->repoName, $table_name_answers_repository, 'name')) {
            $response = createErrorResponse('already_exists', 'Repository already exists', 409);
        } else {
            // Insert the new repository
            $result = $wpdb->insert(
                $table_name_answers_repository,
                array('name' => sanitize_text_field($body->repoName))
            );

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert repository', 500);
            } else {
                $repo_id = $wpdb->insert_id;
                $response = createSuccessResponse(null, 201);
                foreach ($body->answers as $answer) {
                    if (!isset($answer->answer) || !isset($answer->value)) {
                        $response = createErrorResponse('invalid_answer', 'Each answer must have "answer" and "value" properties', 400);
                        break;
                    }
                    $result = $wpdb->insert(
                        $table_name_answers,
                        array(
                            'name' => sanitize_text_field($answer->answer),
                            'value' => intval($answer->value),
                            'answer_repo_id' => $repo_id
                        )
                    );
                    if ($result === false) {
                        $response = createErrorResponse('db_insert_error', 'Could not insert answer', 500);
                        break;
                    }
                }
            }
        }

        return $response;
    }
}

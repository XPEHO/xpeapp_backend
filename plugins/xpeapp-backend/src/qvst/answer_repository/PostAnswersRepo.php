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

        // Validate required fields
        if (empty($body)) {
            return createErrorResponse('missing_body', 'No body provided', 400);
        }

        if (!isset($body->repoName) || empty($body->repoName)) {
            return createErrorResponse('missing_param', 'Missing repoName', 400);
        }

        if (!isset($body->answers) || empty($body->answers) || !is_array($body->answers)) {
            return createErrorResponse('missing_param', 'Missing or invalid answers array', 400);
        }

        // Check if a repository with the same name already exists
        if (entityExists($body->repoName, $table_name_answers_repository, 'name')) {
            return createErrorResponse('already_exists', 'Repository already exists', 409);
        }

        // Insert the new repository
        $result = $wpdb->insert(
            $table_name_answers_repository,
            array('name' => sanitize_text_field($body->repoName))
        );

        if ($result === false) {
            return createErrorResponse('db_insert_error', 'Could not insert repository', 500);
        }

        $repo_id = $wpdb->insert_id;

        // Insert answers for the repository
        foreach ($body->answers as $answer) {
            if (!isset($answer->answer) || !isset($answer->value)) {
                return createErrorResponse('invalid_answer', 'Each answer must have "answer" and "value" properties', 400);
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
                return createErrorResponse('db_insert_error', 'Could not insert answer', 500);
            }
        }

        return createSuccessResponse(null, 201);
    }
}

<?php

namespace XpeApp\qvst\answer_repository;

class PostAnswersRepo {
    public static function apiPostAnswersRepo(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        global $wpdb;
        $table_repo = $wpdb->prefix . 'qvst_answers_repository';
        $table_answers = $wpdb->prefix . 'qvst_answers';
        $body = json_decode($request->get_body());

        // Validation
        $response = self::validateAnswersRepoBody($body, $table_repo);
        if ($response) {
            return $response;
        }

        // Insert repository
        $repo_id = $wpdb->insert(
            $table_repo,
            ['name' => sanitize_text_field($body->repoName)]
        ) ? $wpdb->insert_id : null;

        if (!$repo_id) {
            return createErrorResponse('db_insert_error', 'Could not insert repository', 500);
        }

        // Insert answers
        $response = self::insertAnswersList($body->answers, $repo_id, $table_answers, $wpdb);

        return $response ? $response : createSuccessResponse(null, 201);
    }

    private static function validateAnswersRepoBody($body, $table_name_answers_repository)
    {
        if (empty($body)) {
            return createErrorResponse('missing_body', 'No body provided', 400);
        }
        if (!isset($body->repoName) || empty($body->repoName)) {
            return createErrorResponse('missing_param', 'Missing repoName', 400);
        }
        if (!isset($body->answers) || empty($body->answers) || !is_array($body->answers)) {
            return createErrorResponse('missing_param', 'Missing or invalid answers array', 400);
        }
        if (entityExists($body->repoName, $table_name_answers_repository, 'name')) {
            return createErrorResponse('already_exists', 'Repository already exists', 409);
        }
        return null;
    }

    private static function insertAnswersRepository($repoName, $table_name_answers_repository, $wpdb)
    {
        $result = $wpdb->insert(
            $table_name_answers_repository,
            array('name' => sanitize_text_field($repoName))
        );
        if ($result === false) {
            return null;
        }
        return $wpdb->insert_id;
    }

    private static function insertAnswersList($answers, $repo_id, $table_answers, $wpdb)
    {
        foreach ($answers as $answer) {
            if (!isset($answer->answer) || !isset($answer->value)) {
                return createErrorResponse('invalid_answer', 'Each answer must have "answer" and "value" properties', 400);
            }
            $ok = $wpdb->insert(
                $table_answers,
                [
                    'name' => sanitize_text_field($answer->answer),
                    'value' => intval($answer->value),
                    'answer_repo_id' => $repo_id
                ]
            );
            if (!$ok) {
                return createErrorResponse('db_insert_error', 'Could not insert answer', 500);
            }
        }
        return null;
    }
}

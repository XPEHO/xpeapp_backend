<?php

namespace XpeApp\qvst\questions;

class GetCsvFileQuestions {
    public static function apiGetCsvFileQuestions(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        
        // Define allowed origins based on environment
        $allowed_origins = getenv_docker('CORS_ALLOWED_ORIGINS', '');
        $allowed_origins = explode(';', $allowed_origins);

        // Get the origin of the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Add CORS headers if the origin is allowed
        if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        global $wpdb;

        $table_name_questions = $wpdb->prefix . 'qvst_questions';
        $table_name_theme = $wpdb->prefix . 'qvst_theme';
        $table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';
        $table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

        // Query to get all questions with their theme and answer repo info
        $query = "
            SELECT
                question.id as question_id,
                question.text as question_text,
                theme.id as theme_id,
                theme.name as theme_name,
                repo.id as answer_repo_id,
                repo.name as answer_repo_name,
                COALESCE(question.reversed_question, 0) as reversed_question,
                COALESCE(question.no_longer_used, 0) as no_longer_used,
                COALESCE(cq.num_occurrences, 0) as number_asked
            FROM {$table_name_questions} question
            INNER JOIN {$table_name_theme} theme ON question.theme_id = theme.id
            LEFT JOIN {$table_name_answers_repository} repo ON question.answer_repo_id = repo.id
            LEFT JOIN (
                SELECT question_id, COUNT(campaign_id) as num_occurrences
                FROM {$table_name_campaign_questions}
                GROUP BY question_id
            ) cq ON question.id = cq.question_id
            ORDER BY question.id ASC
        ";

        $results = $wpdb->get_results($query);

        if (empty($results)) {
            return createErrorResponse('no_data', 'No questions found', 404);
        }

        // Name of the CSV file
        $filename = 'qvst_questions_export_' . date('Y-m-d_H-i-s') . '.csv';

        // Send the headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create the file
        $file = fopen('php://output', 'w');

        // Add BOM for UTF-8 compatibility with Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Define the CSV headers
        $headers = array(
            'ID',
            'Question',
            'Theme ID',
            'Theme',
            'Answer Repo ID',
            'Answer Repo',
            'Reversed Question',
            'No Longer Used',
            'Number Asked'
        );
        fputcsv($file, $headers, ';');

        // Add each question as a row
        foreach ($results as $question) {
            $row = array(
                $question->question_id,
                $question->question_text,
                $question->theme_id,
                $question->theme_name,
                $question->answer_repo_id ?? '',
                $question->answer_repo_name ?? '',
                $question->reversed_question ? 'Oui' : 'Non',
                $question->no_longer_used ? 'Oui' : 'Non',
                $question->number_asked
            );
            fputcsv($file, $row, ';');
        }

        fclose($file);
        exit();
    }
}

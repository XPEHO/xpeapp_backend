<?php
include_once 'get_answer_by_group_id.php';

function apiGetCsvFileCampaign($request) {
    
    xpeapp_log_request($request);

    // Get the answer group by id
    $response = getAnswerByGroupId($request);

    if (is_wp_error($response)) {
        return $response;
    }

    $data = $response->get_data();

    // Check if the data is empty
    if (empty($data['answers']) || empty($data['questions'] || empty($data['open_answers']))) {
        return new WP_Error('noData', __('Aucune donnée trouvée', 'QVST'));
    }

    // Name of the CSV file
    $filename = 'campaign_data.csv';

    //  Send the headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment;filename=' . $filename);
    header('Pragma: no-cache');

    // Create the file
    $file = fopen('php://output', 'w');

    // Define the headers using CSV using questions
    $headers = array_merge(
        ['Identifiant de réponse'],
        array_map(fn($question) => $question->question_text ?? '', $data['questions']),
        ['Champs libre']
    );
    fputcsv($file, $headers);

    // Assign the answers to a group ID
    $groupedAnswers = [];
    foreach ($data['answers'] as $answer) {
        $groupedAnswers[$answer->answer_group_id][$answer->question_id] = $answer->answer_text;
    }

    // Assign the open answers to its group ID
    $openAnswers = [];
    foreach ($data['open_answers'] as $open) {
        $openAnswers[$open->answer_group_id] = $open->open_answer_text;
    }

    // Define the basic ID to identify users
    $rowIndex = 1;
    foreach ($groupedAnswers as $groupId => $answers) {
        // Add the user id
        $row = [$rowIndex++];

        // Add the answers following the questions order
        foreach ($data['questions'] as $question) {
            $row[] = $answers[$question->question_id] ?? '';
        }

        // Add the open answer
        $row[] = $openAnswers[$groupId] ?? '';

        fputcsv($file, $row);
    }

    // Close the file
    fclose($file);
    exit();
}


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

    $file = fopen('php://output', 'w');

    // Add the headers custom ID and free field
    $headers = array_merge(
        ['Identifiant de réponse'],
        array_map(fn($question) => $question->text ?? '', $data['questions']),
        ['Champs libre']
    );
    fputcsv($file, $headers);

    // Group the answers by group ID
    $groupedAnswers = [];
    foreach ($data['answers'] as $answer) {
        $groupedAnswers[$answer->answer_group_id][$answer->question_id] = $answer->name;
    }

    // Associate the user with the open answer
    $openAnswers = [];
    foreach ($data['open_answers'] as $open) {
        $openAnswers[$open->answer_group_id] = $open->text;
    }

    $rowIndex = 1;
    foreach ($groupedAnswers as $groupId => $answers) {
        $row = [$rowIndex++];

        // Add the answers in the right order
        foreach ($data['questions'] as $question) {
            $row[] = $answers[$question->question_id] ?? '';
        }

        // Add the open answer
        $row[] = $openAnswers[$groupId] ?? '';

        fputcsv($file, $row);
    }

    fclose($file);
    exit();
}


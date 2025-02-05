<?php
function apiGetCsvFileCampaign($request) {
    
    xpeapp_log_request($request);

    // Get the answer group by id
    $response = get_answer_group_by_id($request);

    if (is_wp_error($response)) {
        return $response;
    }

    $data = $response->get_data();

    // Verify if data is empty
    if (empty($data['answers']) || empty($data['questions'])) {
        return new WP_Error('noData', __('Aucune donnée trouvée', 'QVST'));
    }

    // Name of CSV FILE
    $filename = 'campaign_data.csv';

    // Open the file in write mode
    $file = fopen('php://output', 'w');

    // HEADERS
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment;filename=' . $filename);
    header('Pragma: no-cache');

    // Write the headers of the CSV file
    fputcsv($file, array('question_id', 'answer_id', 'answer_group_id', 'name', 'campaign_id', 'question_text'));

    // Write the data of the answers
    foreach ($data['answers'] as $answer) {
        fputcsv($file, array(
            $answer->question_id,
            $answer->answer_id,
            $answer->answer_groupe_id,
            $answer->name,
            '', 
            '' 
        ));
    }

    //  Write the data of the questions
    foreach ($data['questions'] as $question) {
        fputcsv($file, array(
            '', 
            '', 
            '', 
            '',
            $question->campaign_id,
            $question->text
        ));
    }

    fclose($file);

    exit();
}
?>
<?php
function getAnswerByGroupId($request) {

    xpeapp_log_request($request);
    
    // Utiliser la classe $wpdb pour effectuer une requête SQL
    /** @var wpdb $wpdb */
    global $wpdb;

    $table_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
    $table_answers = $wpdb->prefix . 'qvst_answers';
    $table_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
    $table_qvst_questions = $wpdb->prefix . 'qvst_questions';
    $table_qvst_open_answer = $wpdb->prefix . 'qvst_open_answers';

    $params = $request->get_params();

    if (empty($params)) {
        return new WP_Error('noParams', __('Aucun paramètre', 'Campaign'));
    }

    $response = null;

    try {
        // Get all answers of the campaign
        $answers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT question_id, answer_group_id, `name` as answer_text
                FROM $table_campaign_answers
                JOIN $table_answers ON (answer_id = $table_answers.id)
                WHERE campaign_id = %d
                ORDER BY question_id",
                $params['campaign_id']
            )
        );

        // Get all questions of the campaign
        $questions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT question_id, $table_qvst_questions.`text` as question_text
                FROM $table_campaign_questions
                JOIN $table_qvst_questions ON (question_id = $table_qvst_questions.id)
                WHERE campaign_id = %d
                ORDER BY question_id",
                $params['campaign_id']
            )
        );

        // Get all open answers of the campaign
        $open_answers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT `text` as open_answer_text, answer_group_id FROM $table_qvst_open_answer
                WHERE answer_group_id IN (
                    SELECT DISTINCT answer_group_id
                    FROM $table_campaign_answers
                    WHERE campaign_id = %d
                )",
                $params['campaign_id']
            )
        );

        if (empty($answers) || empty($questions)) {
            $response = new WP_Error('noID', __('Aucune campagne trouvée', 'QVST'));
        } else {
            $data = array(
                'answers' => $answers,
                'questions' => $questions,
                'open_answers' => $open_answers
            );

            // Return 200 OK status code if success with datas
            $response = new WP_REST_Response($data, 200);
        }
    } catch (\Throwable $th) {
        xpeapp_log(Xpeapp_Log_Level::Error, $th->getMessage());
        $response = new WP_Error('error', __('Erreur', 'QVST'));
    }

    return $response;
}

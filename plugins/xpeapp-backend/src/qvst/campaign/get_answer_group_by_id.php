<?php
function get_answer_group_by_id($request) {

    xpeapp_log_request($request);
    
    // Utiliser la classe $wpdb pour effectuer une requête SQL
    /** @var wpdb $wpdb */
    global $wpdb;

    $table_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
    $table_answers = $wpdb->prefix . 'qvst_answers';
    $table_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
    $table_qvst_questions = $wpdb->prefix . 'qvst_questions';

    $params = $request->get_params();

    if (empty($params)) {
        return new WP_Error('noParams', __('Aucun paramètre', 'Campaign'));
    }

    try {
        // Récupérer toutes les réponses d'une campagne triées par question
        $campaign_classify = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT question_id, answer_id, answer_groupe_id, name 
                FROM $table_campaign_answers 
                JOIN $table_answers ON (answer_id = $table_answers.id) 
                WHERE campaign_id = %d 
                ORDER BY question_id",
                $params['campaign_id']
            )
        );

        // Récupérer toutes les questions d'une campagne triées par question
        $campaign_questions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT campaign_id, question_id, `text`
                FROM $table_campaign_questions
                JOIN $table_qvst_questions ON (question_id = $table_qvst_questions.id)
                WHERE campaign_id = %d
                ORDER BY question_id",
                $params['campaign_id']
            )
        );

        if (empty($campaign_classify) || empty($campaign_questions)) {
            return new WP_Error('noID', __('Aucune campagne trouvée', 'QVST'));
        }

        $data = array(
            'answers' => $campaign_classify,
            'questions' => $campaign_questions
        );

        // Return 200 OK status code if success with datas
        return new WP_REST_Response($data, 200);
    } catch (\Throwable $th) {
        xpeapp_log(Xpeapp_Log_Level::Error, $th->getMessage());
        return new WP_Error('error', __('Erreur', 'QVST'));
    }
}
?>
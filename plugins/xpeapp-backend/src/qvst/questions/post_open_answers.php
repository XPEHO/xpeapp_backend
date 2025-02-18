<?php

function postOpenAnswers(WP_REST_Request $request) {

    xpeapp_log_request($request);
    
    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $params = $request->get_params();
    $token = $request->get_header('Authorization');

    $table_open_answers = $wpdb->prefix . 'qvst_open_answers';

    $params = $request->get_params();

    $response = null;

    if (empty($params)) {
        $response = new WP_Error('noParams', __('No parameters found', 'QVST'));
    } elseif (!isset($params['text'])) {
        $response = new WP_Error('noText', __('No text found', 'QVST'));
    } else {
        try {
            $openAnswersToInsert = array(
                'answer_group_id' => $token,
                'text' => $params['text'],
            );

            // save openAnswers
            $wpdb->insert(
                $table_open_answers,
                $openAnswersToInsert,
            );

            // return 201 created status code if success
            $response = new WP_REST_Response(null, 201);
        } catch (\Throwable $th) {
            $response = new WP_Error('error', __('Error', 'QVST'));
        }
    }

    return $response;
}

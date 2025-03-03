<?php

include_once __DIR__ . '/../../utils.php';

function apiPostEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $params = $request->get_params();

    $response = null;

    $validation_error = validateParams($params, ['type_id']);
    if ($validation_error) {
        $response = $validation_error;
    } elseif (!entityExists($params['type_id'], $table_events_type)) {
        $response = createErrorResponse('invalid_type_id', 'Invalid type does not exist', 400);
    } else {
        try {
            $result = $wpdb->insert($table_events, prepareData($params, ['date', 'heure_debut', 'heure_fin', 'titre', 'lieu', 'topic', 'type_id']));

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert event', 500);
            } else {
                $response = createSuccessResponse(null, 201);
            }
        } catch (\Throwable $th) {
            $response = createErrorResponse('error', 'Error', 500);
        }
    }

    return $response;
}
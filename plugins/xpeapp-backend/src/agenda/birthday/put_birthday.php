<?php

include_once __DIR__ . '/../../utils.php';

function apiPutBirthday(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $params = $request->get_params();

    $response = null;

    $validation_error = validateParams($params, ['id']);
    if ($validation_error) {
        $response = $validation_error;
    } elseif (!entityExists($params['id'], $table_birthday)) {
        $response = createErrorResponse('not_found', 'Birthday not found', 404);
    } else {
        $result = $wpdb->update($table_birthday, prepareData($params, ['first_name', 'birthdate', 'email']), ['id' => intval($params['id'])]);

        if ($result === false) {
            $response = createErrorResponse('db_update_error', 'Could not update birthday', 500);
        } else {
            $response = createSuccessResponse(null, 204);
        }
    }

    return $response;
}
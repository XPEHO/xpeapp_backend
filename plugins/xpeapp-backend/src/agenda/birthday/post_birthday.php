<?php

include_once __DIR__ . '/../../utils.php';

function apiPostBirthday(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $params = $request->get_params();

    $response = null;

    $validation_error = validateParams($params, ['first_name', 'birthdate', 'email']);
    if ($validation_error) {
        $response = $validation_error;
    } else {
        try {
            $result = $wpdb->insert($table_birthday, prepareData($params, ['first_name', 'birthdate', 'email']));

            if ($result === false) {
                $response = createErrorResponse('db_insert_error', 'Could not insert birthday', 500);
            } else {
                $response = createSuccessResponse(null, 201);
            }
        } catch (\Throwable $th) {
            $response = createErrorResponse('error', 'Error', 500);
        }
    }

    return $response;
}
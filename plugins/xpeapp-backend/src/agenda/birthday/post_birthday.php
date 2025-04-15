<?php

include_once __DIR__ . '/../../utils.php';

function apiPostBirthday(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $params = $request->get_params();

    // Check if the parameters are valid
    $validation_error = validateParams($params, ['first_name', 'birthdate']);
    if ($validation_error) {
        $response = $validation_error;
    // Check if a birthday for the same email already exists
    } else {
        // Insert the birthday into the database
        $result = $wpdb->insert($table_birthday, prepareData($params, ['first_name', 'birthdate', 'email']));

        if ($result === false) {
            $response = createErrorResponse('db_insert_error', 'Could not insert birthday', 500);
        } else {
            $response = createSuccessResponse(null, 201);
        }
    }

    return $response;
}
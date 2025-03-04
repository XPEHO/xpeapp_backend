<?php

include_once __DIR__ . '/../../utils.php';

function apiGetBirthdayById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $id = $request->get_param('id');

    $response = null;

    if (empty($id)) {
        $response = createErrorResponse('noParams', 'No parameters for id', 400);
    } elseif (!entityExists($id, $table_birthday)) {
        $response = createErrorResponse('not_found', 'Birthday not found', 404);
    } else {
        $query = $wpdb->prepare("
            SELECT first_name, birthdate, email
            FROM $table_birthday
            WHERE id = %d
        ", intval($id));
        $birthday = $wpdb->get_row($query);

        if ($birthday) {
            $response = createSuccessResponse($birthday);
        } else {
            $response = createErrorResponse('not_found', 'Error finding birthday', 404);
        }
    }

    return $response;
}
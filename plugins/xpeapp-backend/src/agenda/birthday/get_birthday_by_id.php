<?php

include_once __DIR__ . '/../../utils.php';

function apiGetBirthdayById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_birthday = $wpdb->prefix . 'agenda_birthday';

    $id = $request->get_param('id');

    if (empty($id)) {
        return createErrorResponse('noParams', 'No parameters for id', 400);
    }

    if (!entityExists($id, $table_birthday)) {
        return createErrorResponse('not_found', 'Birthday not found', 404);
    }

    $query = $wpdb->prepare("
        SELECT first_name, birthdate, email
        FROM $table_birthday
        WHERE id = %d
    ", intval($id));
    $birthday = $wpdb->get_row($query);

    if ($birthday) {
        return createSuccessResponse($birthday);
    } else {
        return createErrorResponse('not_found', 'Error finding birthday', 404);
    }
}
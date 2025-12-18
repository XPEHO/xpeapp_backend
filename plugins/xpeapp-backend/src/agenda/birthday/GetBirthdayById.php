<?php

namespace XpeApp\agenda\birthday;

class GetBirthdayById {
    public static function ApiGetBirthdayById(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_birthday = $wpdb->prefix . 'agenda_birthday';

        $id = $request->get_param('id');

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the birthday exists
        } elseif (!entityExists($id, $table_birthday)) {
            $response = createErrorResponse('not_found', 'Birthday not found', 404);
        } else {
            // Get the birthday from the database
            $query = $wpdb->prepare(
                "SELECT * FROM $table_birthday WHERE id = %d",
                intval($id)
            );
            $birthday = $wpdb->get_row($query);

            if ($birthday) {
                $response = createSuccessResponse($birthday);
            } else {
                $response = createErrorResponse('db_get_error', 'Could not get birthday', 400);
            }
        }

        return $response;
    }
}

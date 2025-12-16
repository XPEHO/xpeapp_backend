<?php

namespace XpeApp\agenda\birthday;

class put_birthday {
    public static function putBirthday(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_birthday = $wpdb->prefix . 'agenda_birthday';

        $id = $request->get_param('id');
        $params = $request->get_params();

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the birthday exists
        } elseif (!entityExists($params['id'], $table_birthday)) {
            $response = createErrorResponse('not_found', 'Birthday not found', 404);
        } else {
            // Update the birthday in the database
            $result = $wpdb->update($table_birthday, prepareData($params, ['first_name', 'birthdate', 'email']), ['id' => intval($params['id'])]);

            if ($result === false) {
                $response = createErrorResponse('db_update_error', 'Could not update birthday', 500);
            } else {
                $response = createSuccessResponse(null, 204);
            }
        }

        return $response;
    }
}

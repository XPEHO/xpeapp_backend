<?php

namespace XpeApp\agenda\birthday;

class DeleteBirthday {
    public static function ApiDeleteBirthday(\WP_REST_Request $request)
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
            // Delete the birthday from the database
            $result = $wpdb->delete(
                $table_birthday,
                array(
                    'id' => intval($id)
                )
            );
            
            if ($result === false) {
                $response = createErrorResponse('db_delete_error', 'Could not delete birthday', 500);
            } else {
                $response = createSuccessResponse(null, 204);
            }
        }

        return $response;
    }
}

<?php

namespace XpeApp\agenda\events;

class delete_events {
    public static function deleteEvents(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events = $wpdb->prefix . 'agenda_events';

        $id = $request->get_param('id');

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the event exists
        } elseif (!entityExists($id, $table_events)) {
            $response = createErrorResponse('not_found', 'Event not found', 404);
        } else {
            // Delete the event from the database
            $result = $wpdb->delete(
                $table_events,
                array(
                    'id' => intval($id)
                )
            );
            
            if ($result === false) {
                $response = createErrorResponse('db_delete_error', 'Could not delete event', 500);
            } else {
                $response = createSuccessResponse(null, 204);
            }
        }

        return $response;
    }
}
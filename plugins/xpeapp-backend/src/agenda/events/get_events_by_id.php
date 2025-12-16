<?php

namespace XpeApp\agenda\events;

class get_events_by_id {
    public static function getEventsById(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        // Utiliser la classe $wpdb pour effectuer une requête SQL
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
            // Get the event from the database
            $query = $wpdb->prepare(
                "SELECT * FROM $table_events te WHERE te.id = %d",
                intval($id)
            );
            $event = $wpdb->get_row($query);

            if ($event) {
                $response = createSuccessResponse($event);
            } else {
                $response = createErrorResponse('db_get_error', 'Could not get event', 400);
            }
        }

        return $response;
    }
}

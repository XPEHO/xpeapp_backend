<?php

namespace XpeApp\agenda\events_types;

class GetEventsTypesById {
    public static function apiGetEventsTypesById(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $id = $request->get_param('id');

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the event type exists
        } elseif (!entityExists($id, $table_events_type)) {
            $response = createErrorResponse('not_found', 'Event type not found', 404);
        } else {
            // Get the event type from the database
            $query = $wpdb->prepare(
                "SELECT * FROM $table_events_type tet WHERE tet.id = %d",
                intval($id)
            );
            $events_type = $wpdb->get_row($query);

            if ($events_type) {
                $response = $events_type;
            } else {
                // Return an error if the event type was not found
                $response = createErrorResponse('db_get_error', 'Could not get event type', 500);
            }
        }

        return $response;
    }
}

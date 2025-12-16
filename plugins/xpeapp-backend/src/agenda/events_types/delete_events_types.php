<?php

namespace XpeApp\agenda\events_types;

class delete_events_types {
    public static function deleteEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';
        $table_events = $wpdb->prefix . 'agenda_events';

        $id = $request->get_param('id');

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the event type exists
        } elseif (!entityExists($id, $table_events_type)) {
            $response = createErrorResponse('not_found', 'Event type not found', 404);
        // Check if an event has this type assigned
        } elseif (entityExists($id, $table_events, 'type_id')) {
            $response = createErrorResponse('event_exists', 'Cannot delete because event type has assigned events', 409);
        } else {
            // Delete the event type from the database
            $result = $wpdb->delete(
                $table_events_type,
                array(
                    'id' => intval($id)
                )
            );

            // Check if the delete was successful
            if ($result === false) {
                $response = createErrorResponse('db_delete_error', 'Could not delete event type', 500);
            } else {
                // Return a 204 response
                $response = createSuccessResponse('Event type deleted', 204);
            }
        }

        return $response;
    }
}

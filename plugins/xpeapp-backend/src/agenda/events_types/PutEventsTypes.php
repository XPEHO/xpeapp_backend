<?php

namespace XpeApp\agenda\events_types;

class PutEventsTypes {
    public static function ApiPutEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        // get the params from the request body
        $id = $request->get_param('id');
        $params = $request->get_params();

        // Check if the parameters are valid
        if (empty($id)) {
            $response = createErrorResponse('missing_id', 'Missing id parameter', 400);
        // Check if the event type exists
        } elseif (!entityExists($id, $table_events_type)) {
            $response = createErrorResponse('not_found', 'Event type not found', 404);
        // Check if an event type with the same label already exists if its provided
        } elseif (!empty($params['label']) && entityExistsWithDifferentId($params['label'], $table_events_type, 'label', $id)) {
            $response = createErrorResponse('already_exists', 'Event type already exists', 409);
        } else {
            // Update the event type in the database
            $result = $wpdb->update(
                $table_events_type,
                prepareData($params, ['label', 'color_code']),
                array('id' => intval($id))
            );

            if ($result === false) {
                $response = createErrorResponse('db_update_error', 'Could not update event type', 500);
            } else {
                $response = createSuccessResponse(null, 204);
            }
        }

        return $response;
    }
}

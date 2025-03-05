<?php

function apiGetEventsById(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $id = $request->get_param('id');

    // Initialize the response variable
    $response = null;

    // Get the id from the request body
    if (empty($id)) {
        $response = createErrorResponse('noParams', 'No parameters for id', 400);
    } else {
        // Check if the event exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events WHERE id = %d", intval($id)));

        if ($exists == 0) {
            $response = createErrorResponse('not_found', 'Event not found', 404);
        } else {
            // Get the event and its type from the database
            $query = $wpdb->prepare("
                SELECT te.id, te.date, te.start_time, te.end_time, te.title, te.location, te.topic, tet.id as type_id, tet.label as type_label
                FROM $table_events te
                LEFT JOIN $table_events_type tet ON te.type_id = tet.id
                WHERE te.id = %d
            ", intval($id));
            $event = $wpdb->get_row($query);

            // Check if the event was found
            if ($event) {
                // Format the result to include event type as an object
                $formatted_event = (object) [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date' => $event->date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'location' => $event->location,
                    'topic' => $event->topic,
                    'type' => (object) [
                        'id' => $event->type_id,
                        'label' => $event->type_label,
                    ]
                ];
                $response = $formatted_event;
            } else {
                // Return an error if the event was not found
                $response = createErrorResponse('not_found', 'Error finding event', 404);

            }
        }
    }

    return $response;
}
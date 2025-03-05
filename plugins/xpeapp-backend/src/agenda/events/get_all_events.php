<?php

include_once __DIR__ . '/../../utils.php';

function apiGetAllEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the page parameter from the query parameters
    $page = $request->get_param('page');

    // Build the query using the utility function
    $query = buildQueryWithPaginationAndFilters($table_events, $page, 'date');

    $results = $wpdb->get_results($query);

    // Format the results to include event type as an object and return directly
    return array_map(function($event) {
        return (object) [
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
    }, $results);
}
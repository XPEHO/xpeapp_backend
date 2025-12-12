<?php
namespace XpeApp\Agenda\EventsTypes;

class EventsTypesApi {
    public static function getAllEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $query = $wpdb->prepare("SELECT * FROM $table_events_type");
        return $wpdb->get_results($query);
    }
}
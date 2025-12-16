<?php
namespace XpeApp\agenda\events_types;

class get_all_events_types {
    public static function getAllEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $query = $wpdb->prepare("SELECT * FROM $table_events_type");
        return $wpdb->get_results($query);
    }
}

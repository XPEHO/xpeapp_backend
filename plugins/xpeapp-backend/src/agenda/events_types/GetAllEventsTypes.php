<?php
namespace XpeApp\agenda\events_types;

class GetAllEventsTypes {
    public static function ApiGetAllEventsTypes(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_events_type = $wpdb->prefix . 'agenda_events_type';

        $query = $wpdb->prepare("SELECT * FROM $table_events_type");
        return $wpdb->get_results($query);
    }
}

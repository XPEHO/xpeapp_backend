<?php

namespace XpeApp\Agenda\Birthday;

class BirthdayApi {
    public static function getAllBirthdays(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_birthday = $wpdb->prefix . 'agenda_birthday';

        // Get the page parameter from the query parameters
        $page = $request->get_param('page');

        // Build the query using the utility function
        $query = buildQueryWithPaginationAndFilters($table_birthday, $page, 'birthdate');

        return $wpdb->get_results($query);
    }
}
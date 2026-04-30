<?php

namespace XpeApp\agenda\birthday;

class GetAllBirthday {
    public static function apiGetAllBirthdays(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        global $wpdb;

        $table_birthday = $wpdb->prefix . 'agenda_birthday';

        // Get the page parameter from the query parameters
        $page = $request->get_param('page');

        $query = buildQueryWithPaginationAndFilters($table_birthday, $page, 'birthdate', 10, null, null, 'MONTH(birthdate) ASC, DAY(birthdate) ASC');

        return $wpdb->get_results($query);
    }
}

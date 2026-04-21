<?php

namespace XpeApp\idea_box;

class GetCsvFileIdeas {
    public static function apiGetCsvFileIdeas(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        
        // Define allowed origins based on environment
        $allowed_origins = getenv_docker('CORS_ALLOWED_ORIGINS', '');
        $allowed_origins = explode(';', $allowed_origins);

        // Get the origin of the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Add CORS headers if the origin is allowed
        if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        global $wpdb;

        $table_idea_box = $wpdb->prefix . 'idea_box';
        $table_usermeta = $wpdb->prefix . 'usermeta';

        // Query to get all ideas with author information
        $query = "
            SELECT
                i.id,
                i.context,
                i.description,
                i.status,
                i.reason,
                CONCAT_WS(' ', fn.meta_value, ln.meta_value) AS author,
                i.created_at
            FROM {$table_idea_box} i
            LEFT JOIN {$table_usermeta} fn
                ON i.user_id = fn.user_id AND fn.meta_key = 'first_name'
            LEFT JOIN {$table_usermeta} ln
                ON i.user_id = ln.user_id AND ln.meta_key = 'last_name'
            ORDER BY i.created_at DESC
        ";

        $results = $wpdb->get_results($query);

        if (empty($results)) {
            return createErrorResponse('no_data', 'No ideas found', 404);
        }

        // Name of the CSV file
        $filename = 'ideas_export_' . date('Y-m-d_H-i-s') . '.csv';

        // Send the headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create the file
        $file = fopen('php://output', 'w');

        // Add BOM for UTF-8 compatibility with Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Define the CSV headers
        $headers = array(
            'ID',
            'Context',
            'Description',
            'Status',
            'Reason',
            'Author',
            'Created Date'
        );
        fputcsv($file, $headers, ';');

        // Add each idea as a row
        foreach ($results as $idea) {
            $row = array(
                $idea->id,
                $idea->context,
                $idea->description,
                $idea->status,
                $idea->reason ?? '',
                $idea->author ?? '',
                $idea->created_at
            );
            fputcsv($file, $row, ';');
        }

        fclose($file);
        exit();
    }
}

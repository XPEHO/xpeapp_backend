<?php

namespace XpeApp\idea_box;

include_once __DIR__ . '/../utils.php';

class GetCsvFileIdeas {
    public static function apiGetCsvFileIdeas(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }

        global $wpdb;

        $table_idea_box = $wpdb->prefix . 'idea_box';
        $table_usermeta = $wpdb->prefix . 'usermeta';

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

        $filename = 'ideas_export_' . date('Y-m-d_H-i-s') . '.csv';
        setupCsvExportHeaders($filename);

        $file = fopen('php://output', 'w');

        // Add BOM for UTF-8 compatibility with Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

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


<?php

function validateParams($params, $required_params = [])
{
    foreach ($required_params as $param) {
        if (!isset($params[$param])) {
            return createErrorResponse('missing_param', 'Missing ' . $param, 400);
        }
    }
    return null;
}

function entityExists($id, $table)
{
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id = %d", intval($id)));
    return $exists > 0;
}

function createErrorResponse($code, $message, $status)
{
    return new WP_REST_Response(new WP_Error($code, $message, ['status' => $status]), $status);
}

function createSuccessResponse($data = null, $status = 200)
{
    return new WP_REST_Response($data, $status);
}

function prepareData($params, $fields)
{
    return array_filter($params, function($key) use ($fields) {
        return in_array($key, $fields);
    }, ARRAY_FILTER_USE_KEY);
}

function buildQueryWithPaginationAndFilters($table, $page, $date_field, $items_per_page = 10)
{
    global $wpdb;

    $query = "SELECT * FROM $table";
    $condition = "";
    $offset = 0;

    if ($page === 'week') {
        // Filter records in the next 7 days
        $condition = " WHERE $date_field BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($page === 'month') {
        // Filter records in the next 30 days
        $condition = " WHERE $date_field BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } else {
        // Apply pagination
        $page = is_numeric($page) ? intval($page) : 1;
        $offset = ($page - 1) * $items_per_page;
        $condition = " LIMIT $items_per_page OFFSET $offset";
    }

    return $wpdb->prepare($query . $condition);
}
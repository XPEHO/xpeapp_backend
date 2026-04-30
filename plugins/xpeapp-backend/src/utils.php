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

function entityExists($field_value, $table, $field_name = 'id')
{
    global $wpdb;
    $table = esc_sql($table);
    $field_name = esc_sql($field_name);
    $field_value = esc_sql($field_value);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field_name` = %s", $field_value));
    return $exists > 0;
}

function entityExistsWithDifferentId($field_value, $table, $field_name, $id)
{
    global $wpdb;
    $table = esc_sql($table);
    $field_name = esc_sql($field_name);
    $field_value = esc_sql($field_value);
    $id = esc_sql($id);
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE `$field_name` = %s AND `id` != %s ", $field_value, $id));
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

function buildQueryWithPaginationAndFilters(
    string $table,
    $page,
    string $dateField,
    int $itemsPerPage = 10,
    ?string $customQuery = null,
    ?string $endDateField = null,
    ?string $orderByClause = null
) {
    global $wpdb;

    $query = $customQuery ?? "SELECT * FROM {$table}";
    $queryHasWhere = $customQuery !== null && stripos($customQuery, 'WHERE') !== false;
    $conditions = [];
    $limit = '';
    $orderBy = $orderByClause ? " ORDER BY {$orderByClause}" : " ORDER BY {$dateField} DESC";

    // Week / Month filters
    if ($page === 'week' || $page === 'month') {
        $days = $page === 'week' ? 7 : 30;

        if ($endDateField) {
            $conditions[] = "COALESCE({$endDateField}, {$dateField}) >= CURDATE()";
            $conditions[] = "{$dateField} <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";
        } else {
            $conditions[] = "{$dateField} BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL {$days} DAY)";
        }
    } else {
        // Pagination
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $itemsPerPage;
        $limit = " LIMIT {$itemsPerPage} OFFSET {$offset}";
    }

    $where = '';
    if (!empty($conditions)) {
        $prefix = $queryHasWhere ? ' AND ' : ' WHERE ';
        $where = $prefix . implode(' AND ', $conditions);
    }

    return $query . $where . $orderBy . $limit;
}

function setupCsvExportHeaders($filename)
{
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

    // Send the CSV headers
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');
}



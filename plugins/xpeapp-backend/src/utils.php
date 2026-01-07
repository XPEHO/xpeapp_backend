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
    ?string $endDateField = null
) {
    global $wpdb;

    $query = $customQuery ?? "SELECT * FROM {$table}";
    $conditions = [];
    $limit = '';
    $orderBy = " ORDER BY {$dateField} DESC";

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

    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    return $query . $where . $orderBy . $limit;
}


<?php

function validateEventParams($params, $required_params = [])
{
    foreach ($required_params as $param) {
        if (!isset($params[$param])) {
            return createErrorResponse('missing_param', __('Missing ' . $param, 'Agenda'), 400);
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
    return new WP_REST_Response(new WP_Error($code, __($message, 'Agenda'), ['status' => $status]), $status);
}

function prepareEventData($params)
{
    $fields = ['date', 'heure_debut', 'heure_fin', 'titre', 'lieu', 'topic', 'type_id'];
    return array_filter($params, function($key) use ($fields) {
        return in_array($key, $fields);
    }, ARRAY_FILTER_USE_KEY);
}
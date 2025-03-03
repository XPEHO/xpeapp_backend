<?php

function apiPutEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    $params = $request->get_params();
    $response = null;

    $validation_error = validatePutEventParams($params);
    if ($validation_error) {
        return $validation_error;
    }

    if (!entityExists($params['id'], $table_events)) {
        return createErrorResponse('not_found', 'Event not found', 404);
    }

    if (!empty($params['type_id']) && !entityExists($params['type_id'], $table_events_type)) {
        return createErrorResponse('invalid_type_id', 'Invalid type_id does not exist', 400);
    }

    $data = prepareEventData($params);
    $result = $wpdb->update($table_events, $data, array('id' => intval($params['id'])));

    if ($result === false) {
        return createErrorResponse('db_update_error', 'Could not update event', 500);
    }

    return new WP_REST_Response(null, 204);
}

function validatePutEventParams($params)
{
    if (empty($params)) {
        return createErrorResponse('no_params', 'No parameters provided', 400);
    }
    if (empty($params['id'])) {
        return createErrorResponse('missing_id', 'Missing id', 400);
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
    return new WP_REST_Response(new WP_Error($code, __($message, 'Agenda'), array('status' => $status)), $status);
}

function prepareEventData($params)
{
    $fields = ['date', 'heure_debut', 'heure_fin', 'titre', 'lieu', 'topic', 'type_id'];
    $data = array_filter($params, function($key) use ($fields) {
        return in_array($key, $fields);
    }, ARRAY_FILTER_USE_KEY);
    return $data;
}
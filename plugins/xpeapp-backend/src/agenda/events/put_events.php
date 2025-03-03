<?php

function apiPutEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Use the $wpdb class to perform an SQL query
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the parameters from the request body
    $params = $request->get_params();

    // Validate the required parameters
    $validation_error = validatePutEventParams($params);
    if ($validation_error) {
        return $validation_error;
    }

    // Check if the event exists
    if (!eventExists($params['id'], $table_events)) {
        return new WP_REST_Response(new WP_Error('not_found', __('Event not found', 'Agenda'), array('status' => 404)), 404);
    }

    // Check if the type_id is valid in the database
    if (!empty($params['type_id']) && !typeExistsForPut($params['type_id'], $table_events_type)) {
        return new WP_REST_Response(new WP_Error('invalid_type_id', __('Invalid type_id does not exist', 'Agenda'), array('status' => 400)), 400);
    }

    // Prepare the data to update
    $data = prepareEventData($params);

    // Update the event in the database
    $result = $wpdb->update(
        $table_events,
        $data,
        array('id' => intval($params['id']))
    );

    // Check if the update was successful
    if ($result === false) {
        return new WP_REST_Response(new WP_Error('db_update_error', __('Could not update event', 'Agenda'), array('status' => 500)), 500);
    }

    // Return a 204 response
    return new WP_REST_Response(null, 204);
}

function validatePutEventParams($params)
{
    if (empty($params)) {
        return new WP_REST_Response(new WP_Error('no_params', __('No parameters provided', 'Agenda'), array('status' => 400)), 400);
    }
    if (empty($params['id'])) {
        return new WP_REST_Response(new WP_Error('missing_id', __('Missing id', 'Agenda'), array('status' => 400)), 400);
    }
    return null;
}

function eventExists($id, $table_events)
{
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events WHERE id = %d", intval($id)));
    return $exists > 0;
}

function typeExistsForPut($type_id, $table_events_type)
{
    global $wpdb;
    $type_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events_type WHERE id = %d", intval($type_id)));
    return $type_exists > 0;
}

function prepareEventData($params)
{
    $data = array();
    if (!empty($params['date'])) {
        $data['date'] = $params['date'];
    }
    if (!empty($params['heure_debut'])) {
        $data['heure_debut'] = $params['heure_debut'];
    }
    if (!empty($params['heure_fin'])) {
        $data['heure_fin'] = $params['heure_fin'];
    }
    if (!empty($params['titre'])) {
        $data['titre'] = $params['titre'];
    }
    if (!empty($params['lieu'])) {
        $data['lieu'] = $params['lieu'];
    }
    if (!empty($params['topic'])) {
        $data['topic'] = $params['topic'];
    }
    if (!empty($params['type_id'])) {
        $data['type_id'] = $params['type_id'];
    }
    return $data;
}
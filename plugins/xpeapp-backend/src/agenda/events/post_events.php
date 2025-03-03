<?php

function apiPostEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Use the $wpdb class to perform an SQL query
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the parameters from the request body
    $params = $request->get_params();

    // Initialize the response variable
    $response = null;

    // Validate the required parameters
    $validation_error = validateEventParams($params);
    if ($validation_error) {
        $response = $validation_error;
    } else if (!typeExists($params['type_id'], $table_events_type)) {
        // Check if the type_id exists in the wp_agenda_events_type table
        $response = new WP_Error('invalid_type_id', __('Invalid type does not exist', 'Agenda'), array('status' => 400));
    } else {
        try {
            // Insert the new event into the database
            $result = $wpdb->insert(
                $table_events,
                array(
                    'date' => $params['date'],
                    'heure_debut' => $params['heure_debut'],
                    'heure_fin' => $params['heure_fin'],
                    'titre' => $params['titre'],
                    'lieu' => $params['lieu'],
                    'topic' => $params['topic'],
                    'type_id' => $params['type_id'],
                )
            );

            if ($result === false) {
                $response = new WP_Error('db_insert_error', __('Could not insert event', 'Agenda'), array('status' => 500));
            } else {
                $response = new WP_REST_Response(null, 201);
            }
        } catch (\Throwable $th) {
            $response = new WP_Error('error', __('Error', 'Agenda'), array('status' => 500));
        }
    }

    return $response;
}

function validateEventParams($params)
{
    $required_params = ['date', 'heure_debut', 'heure_fin', 'titre', 'lieu', 'topic', 'type_id'];
    foreach ($required_params as $param) {
        if (!isset($params[$param])) {
            return new WP_Error('missing_param', __('Missing ' . $param, 'Agenda'), array('status' => 400));
        }
    }
    return null;
}

function typeExists($type_id, $table_events_type)
{
    global $wpdb;
    $type_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events_type WHERE id = %d", intval($type_id)));
    return $type_exists > 0;
}
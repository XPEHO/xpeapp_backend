<?php

function apiPutEvents(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events = $wpdb->prefix . 'agenda_events';
    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Get the parameters from the request body
    $params = $request->get_params();

    $response = null;

    if (empty($params)) {
        $response = new WP_REST_Response(new WP_Error('no_params', __('No parameters provided', 'Agenda'), array('status' => 400)), 400);
    } else {
        // Check if the id is provided and exists in the database
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events WHERE id = %d", intval($params['id'])));

        if ($exists == 0) {
            $response = new WP_REST_Response(new WP_Error('not_found', __('Event not found', 'Agenda'), array('status' => 404)), 404);
        } else {
            // Check if the type_id is valid in the database 
            if (!empty($params['type_id'])) {
                $type_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_events_type WHERE id = %d", intval($params['type_id'])));
                if ($type_exists == 0) {
                    $response = new WP_REST_Response(new WP_Error('invalid_type_id', __('Invalid type_id does not exist', 'Agenda'), array('status' => 400)), 400);
                }
            }

            if ($response === null) {
                // Prepare the data to update
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

                // Update the event in the database
                $result = $wpdb->update(
                    $table_events,
                    $data,
                    array(
                        'id' => intval($params['id'])
                    )
                );

                // Check if the update was successful
                if ($result === false) {
                    $response = new WP_REST_Response(new WP_Error('db_update_error', __('Could not update event', 'Agenda'), array('status' => 500)), 500);
                } else {
                    // Return a 204 response
                    $response = new WP_REST_Response(null, 204);
                }
            }
        }
    }

    return $response;
}
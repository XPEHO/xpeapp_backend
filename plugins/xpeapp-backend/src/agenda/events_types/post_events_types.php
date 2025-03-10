<?php

function apiPostEventsTypes(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    // Utiliser la classe $wpdb pour effectuer une requête SQL
    global $wpdb;

    $table_events_type = $wpdb->prefix . 'agenda_events_type';

    // Récupérer le label depuis le corps de la requête
    $label = $request->get_param('label');
    $color_code = $request->get_param('color_code');

    if (empty($label) ) {
        return new WP_Error('no_label', __('No label provided', 'Agenda'), array('status' => 400));
    }elseif (empty($color_code)) {
        return new WP_Error('no_color_code', __('No color code provided', 'Agenda'), array('status' => 400));
    }

    // Insérer le nouveau type d'événement dans la base de données
    $result = $wpdb->insert(
        $table_events_type,
        array(
            'label' => $label,
            'color_code' => $color_code
        )
    );

    if ($result === false) {
        return new WP_Error('db_insert_error', __('Could not insert event type', 'Agenda'), array('status' => 500));
    }

    return new WP_REST_Response(null, 201);
}
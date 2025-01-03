<?php

// Fonction pour mettre à jour le mot de passe de l'utilisateur
function api_update_user_password(WP_REST_Request $request) {
    xpeapp_log_request($request);

    $user_id = get_current_user_id();
    $initial_password = $request->get_param('initial_password');
    $new_password = $request->get_param('password');
    $new_password_repeat = $request->get_param('password_repeat');

    if (empty($initial_password)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - No Password Initial Provided");
        return new WP_Error('no_password_initial_provided', __('The initial password is not provided', 'xpeapp'));
    }

    if (empty($new_password) || empty($new_password_repeat)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - No Password Provided");
        return new WP_Error('no_password_provided', __('At least one occurrences of the new password is not provided', 'xpeapp'));

    }

    // Check if the initial password is correct
    $user = wp_authenticate(get_userdata($user_id)->user_login, $initial_password);

    if (is_wp_error($user)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - Incorrect Password Initial");
        return new WP_Error('incorrect_password', __('The initial password is incorrect', 'xpeapp'));
    }


    if ($new_password !== $new_password_repeat) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - Passwords do not match");
        return new WP_Error('password_mismatch', __('Passwords do not match', 'xpeapp'));
    }

    $user_data = array(
        'ID' => $user_id,
        'user_pass' => $new_password
    );

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        return $result;
    }

    return new WP_REST_Response(null, 204);
}

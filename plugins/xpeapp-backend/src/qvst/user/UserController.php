<?php

namespace XpeApp\qvst\user;
use Xpeapp_Log_Level;

class UserController {
   public static function apiGetUser(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        if(empty($request->get_header('email'))) {
            return new \WP_Error('noUserIdHeader', __('No userId header', 'QVST'));
        }

        $user = get_user_by('email', $request->get_header('email'));

        if (empty($user)) {
            return new \WP_Error('noUser', __('No user found', 'QVST'));
        }

        return $user->ID;
    }

    public static function apiGetUserInfos(\WP_REST_Request $request)
    {
    xpeapp_log_request($request);

    $user = wp_get_current_user();
    if ($user->ID == 0) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/user-infos - User not logged in");
        return new \WP_Error('not_logged_in', __('You are not logged in', 'xpeapp'), array('status' => 401));
    }
    $data = array(
        'id' => $user->ID,
        'email' => $user->data->user_email,
        'firstname' => $user->first_name ? $user->first_name : '',
        'lastname' => $user->last_name ? $user->last_name : '',
    );

    // Return 200 OK status code if success with datas
    return new \WP_REST_Response($data, 200);

    }

    public static function apiUpdateUserPassword(\WP_REST_Request $request) {
    xpeapp_log_request($request);

    $user_id = get_current_user_id();
    $initial_password = $request->get_param('initial_password');
    $new_password = $request->get_param('password');
    $new_password_repeat = $request->get_param('password_repeat');

    $error = null;

    if (empty($initial_password)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - No Password Initial Provided");
        $error = new \WP_Error('no_password_initial_provided', __('The initial password is not provided', 'xpeapp'));
    } elseif (empty($new_password) || empty($new_password_repeat)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - No Password Provided");
        $error = new \WP_Error('no_password_provided', __('At least one occurrences of the new password is not provided', 'xpeapp'));
    } else {
        // Check if the initial password is correct
        $user = wp_authenticate(get_userdata($user_id)->user_login, $initial_password);

        if (is_wp_error($user)) {
            xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - Incorrect Password Initial");
            $error = new \WP_Error('incorrect_password', __('The initial password is incorrect', 'xpeapp'));
        } elseif ($new_password !== $new_password_repeat) {
            xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/update-password - Passwords do not match");
            $error = new \WP_Error('password_mismatch', __('Passwords do not match', 'xpeapp'));
        }
    }

    if ($error) {
        return $error;
    }

    $user_data = array(
        'ID' => $user_id,
        'user_pass' => $new_password
    );

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        return $result;
    }

    return new \WP_REST_Response(null, 204);
    }


    public static function apiUpdateLastConnexion(\WP_REST_Request $request) {
        xpeapp_log_request($request);
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new \WP_Error('no_user', 'Utilisateur non authentifié', ['status' => 401]);
        }
        $now = current_time('mysql');
        update_user_meta($user_id, 'last_connection', $now);
        return new \WP_REST_Response(null, 201);
    }

    public static function apiGetAllLastConnexions(\WP_REST_Request $request) {
        xpeapp_log_request($request);
        $args = [
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_key' => 'last_connection',
            'fields' => ['ID'],
        ];
        $users = get_users($args);
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'last_connection' => get_user_meta($user->ID, 'last_connection', true)
            ];
        }
        return $result;
    }

    public static function apiResetUserPassword(\WP_REST_Request $request) {
    xpeapp_log_request($request);

    $email = $request->get_param('email');
    $new_password = $request->get_param('password');
    $new_password_repeat = $request->get_param('password_repeat');

    $error = null;

    if (empty($email)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/reset-password - No email provided");
        $error = new \WP_Error('no_email_provided', __('The email address is required', 'xpeapp'));
    } elseif (empty($new_password) || empty($new_password_repeat)) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/reset-password - No password provided");
        $error = new \WP_Error('no_password_provided', __('Both password and password confirmation are required', 'xpeapp'));
    } elseif (strlen($new_password) < 8) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/reset-password - Password too short");
        $error = new \WP_Error('password_too_short', __('Password must be at least 8 characters long', 'xpeapp'));
    } elseif ($new_password !== $new_password_repeat) {
        xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/reset-password - Passwords do not match");
        $error = new \WP_Error('password_mismatch', __('Passwords do not match', 'xpeapp'));
    } else {
        // Check if target user exists by email
        $target_user = get_user_by('email', $email);
        if (!$target_user) {
            xpeapp_log(Xpeapp_Log_Level::Warn, "PUT xpeho/v1/reset-password - User not found: $email");
            $error = new \WP_Error('user_not_found', __('The specified user was not found', 'xpeapp'));
        }
    }

    if ($error) {
        return $error;
    }

    $target_user = get_user_by('email', $email);

    $user_data = array(
        'ID' => $target_user->ID,
        'user_pass' => $new_password
    );

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        return $result;
    }

    return new \WP_REST_Response(null, 204);
    }
}

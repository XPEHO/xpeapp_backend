<?php

namespace XpeApp\qvst\user;

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

}
<?php

namespace XpeApp\qvst\user;

class get_user_infos {
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
}

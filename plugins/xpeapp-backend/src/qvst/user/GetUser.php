<?php

namespace XpeApp\qvst\user;

class GetUser {
	public static function api_get_user(\WP_REST_Request $request)
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
}
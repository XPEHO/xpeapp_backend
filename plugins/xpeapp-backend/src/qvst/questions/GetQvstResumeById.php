<?php

namespace XpeApp\qvst\questions;

class GetQvstResumeById {
	public static function apiGetQvstResumeById(\WP_REST_Request $request)
{
	xpeapp_log_request($request);

	$params = $request->get_params();

	if (empty($params) || !isset($params['id'])) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/{id}:resume - No ID parameter");
		return new \WP_Error('noID', __('No ID', 'QVST'));
	}

	$question_id = intval($params['id']);

	require_once __DIR__ . '/questions_utils.php';
	$details = get_question_details($question_id);

	$meta = $details['meta'];
	if (empty($meta)) {
		xpeapp_log(\Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst/$question_id:resume - No data found for ID");
		return new \WP_Error('noID', __('No ID', 'QVST'));
	}

	$data = [
		'id' => $meta->id,
		'theme' => $meta->theme,
		'question' => $meta->question,
		'numberOfAnswers' => (int) $meta->numberOfAnswers,
		'averageAnswer' => $meta->averageAnswer,
		'maxValueAnswer' => $meta->maxValueAnswer,
	];

	return $data;
}
}
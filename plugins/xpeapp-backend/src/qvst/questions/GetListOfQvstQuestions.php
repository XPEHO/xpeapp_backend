<?php

namespace XpeApp\qvst\questions;

include_once __DIR__ . '/../../utils.php';

class GetListOfQvstQuestions {
	public static function apiGetQvst(\WP_REST_Request $request)
	{
		xpeapp_log_request($request);
		// Utiliser la classe $wpdb pour effectuer une requête SQL
		/** @var wpdb $wpdb */
		global $wpdb;

		// Nom des tables personnalisées
		$table_name_questions = $wpdb->prefix . 'qvst_questions';
		$table_name_answers = $wpdb->prefix . 'qvst_answers';
		$table_name_theme = $wpdb->prefix . 'qvst_theme';
		$table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

		$includeNoLongerUsed = filter_var($request->get_param('include_no_longer_used'), FILTER_VALIDATE_BOOLEAN);
		$page = max(1, intval($request->get_param('page') ?: 1));
		$perPage = max(1, intval($request->get_param('per_page') ?: 10));

		$baseQuery = "
			SELECT
			question.id as question_id,
			question.text as question,
			theme.id as theme_id,
			theme.name as theme_name,
			question.answer_repo_id,
			answer.id,
			answer.name,
			answer.value,
			COALESCE(cq.num_occurrences, 0) as numberAsked,
			COALESCE(question.reversed_question, 0) as reversed_question,
			COALESCE(question.no_longer_used, 0) as no_longer_used
			FROM {$table_name_questions} question
			INNER JOIN {$table_name_theme} theme on question.theme_id = theme.id
			INNER JOIN {$table_name_answers} answer on answer.answer_repo_id = question.answer_repo_id
			LEFT JOIN (
				SELECT question_id, COUNT(campaign_id) as num_occurrences
				FROM {$table_name_campaign_questions}
				GROUP BY question_id
			) cq ON question.id = cq.question_id
		";

		$countQuery = "
			SELECT COUNT(DISTINCT question.id)
			FROM {$table_name_questions} question
			INNER JOIN {$table_name_theme} theme on question.theme_id = theme.id
		";

		// Si un paramètre id est fourni, on récupère une question précise
		$idParam = $request->get_param('id');
		$results = array();
		$totalQuestions = null;
		$response = null;

		if (!empty($idParam)) {
			$question_id = intval($idParam);
			$query = $baseQuery . " WHERE question.id = %d";
			$results = $wpdb->get_results($wpdb->prepare($query, $question_id));
		} else {
			$whereClause = '';
			$countWhereClause = '';

			// Exclure les questions no_longer_used sauf demande explicite
			if (!$includeNoLongerUsed) {
				$whereClause = " WHERE COALESCE(question.no_longer_used, 0) = 0";
				$countWhereClause = " WHERE COALESCE(question.no_longer_used, 0) = 0";
			}

			$totalQuestions = intval($wpdb->get_var($countQuery . $countWhereClause));
			$offset = ($page - 1) * $perPage;
			$idsQuery = "
				SELECT question.id
				FROM {$table_name_questions} question
				INNER JOIN {$table_name_theme} theme on question.theme_id = theme.id
				{$whereClause}
				GROUP BY question.id, theme.id
				ORDER BY theme.id, question.id
				LIMIT {$perPage} OFFSET {$offset}
			";
			$questionIds = $wpdb->get_col($idsQuery);

			if (!empty($questionIds)) {
				$placeholders = implode(',', array_fill(0, count($questionIds), '%d'));
				$resultsQuery = $baseQuery . " WHERE question.id IN ($placeholders) ORDER BY theme.id, question.id, answer.value DESC";
				$results = $wpdb->get_results(call_user_func_array([$wpdb, 'prepare'], array_merge([$resultsQuery], $questionIds)));
			}
		}

		// Vérifier s'il y a des résultats
		if ($results) {
			$data = array();
			foreach ($results as $result) {
				$questionExists = false;
				foreach ($data as &$item) {
					if ($item['question_id'] === $result->question_id) {
						$item['answers'][] = array(
							'id' => $result->id,
							'answer' => $result->name,
							'value' => $result->value
						);
						$questionExists = true;
						break;
					}
				}
				unset($item);

				if (!$questionExists) {
					$data[] = array(
						'question_id' => $result->question_id,
						'question' => $result->question,
						'theme' => $result->theme_name,
						'theme_id' => $result->theme_id,
						'answer_repo_id' => $result->answer_repo_id,
						'numberAsked' => intval($result->numberAsked),
						'reversed_question' => (bool) $result->reversed_question,
						'no_longer_used' => (bool) $result->no_longer_used,
						'answers' => array(
							array(
								'id' => $result->id,
								'answer' => $result->name,
								'value' => $result->value
							)
						)
					);
				}
			}

			$response = new \WP_REST_Response($data, 200);
			if (empty($idParam)) {
				$response->header('X-WP-Total', isset($totalQuestions) ? (string) $totalQuestions : (string) count($data));
				$response->header('X-WP-TotalPages', isset($totalQuestions) ? (string) max(1, (int) ceil($totalQuestions / $perPage)) : '1');
			}
		} else {
			if (!empty($idParam)) {
				xpeapp_log(Xpeapp_Log_Level::Warn, "GET xpeho/v1/qvst No query result found");
				$response = new \WP_REST_Response(array(
					"error" => "Not Found",
					"message" => "No QVST have been found"
				), 404);
			} else {
				// Réponse vide avec les en-têtes de pagination
				$response = new \WP_REST_Response(array(), 200);
				$response->header('X-WP-Total', isset($totalQuestions) ? (string) $totalQuestions : '0');
				$response->header('X-WP-TotalPages', isset($totalQuestions) ? (string) max(1, (int) ceil($totalQuestions / $perPage)) : '1');
			}
		}

		return $response;
	}
}
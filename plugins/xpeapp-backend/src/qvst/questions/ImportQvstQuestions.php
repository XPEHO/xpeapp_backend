<?php

namespace XpeApp\qvst\questions;

class ImportQvstQuestions {
	public static function apiImportQvst($request)
{
	xpeapp_log_request($request);

	try {
		// 1/ Get the json from body
		$body = json_decode($request->get_body());

		// 2/ Check if the JSON are empty
		if (empty($body)) {
            xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - No JSON in request body");
			return new \WP_Error('noFile', __('No JSON', 'QVST'));
		} else {

			// 3/ Get the key questions
			$jsonContent = $body->questions;

			// 4/ Decode file in $questions
			$questions = array();
			$index = 0;
			foreach ($jsonContent as $key => $value) {
				$questions[$index]['id'] = isset($value->id) ? intval($value->id) : null;
				$questions[$index]['id_theme'] = $value->id_theme;
				$questions[$index]['question'] = $value->question;
				$questions[$index]['response_repo'] = $value->response_repo;
				$questions[$index]['reversed_question'] = isset($value->reversed_question) ? (int) filter_var($value->reversed_question, FILTER_VALIDATE_BOOLEAN) : 0;
				$questions[$index]['no_longer_used'] = isset($value->no_longer_used) ? (int) filter_var($value->no_longer_used, FILTER_VALIDATE_BOOLEAN) : 0;
				$index++;
			}


			// 5/ Check for all questions if the theme exists
			/** @var wpdb $wpdb */
			global $wpdb;

			// Nom de la table personnalisée
			$table_name_theme = $wpdb->prefix . 'qvst_theme';
			$table_name_questions = $wpdb->prefix . 'qvst_questions';
			$table_name_answers_repository = $wpdb->prefix . 'qvst_answers_repository';

			$questionsImported = 0;
			$questionsUpdated = 0;
			$questionsNotImported = 0;

			foreach ($questions as $question) {
				// find theme with id
				$theme = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_theme WHERE id = %d", $question['id_theme']));
				$response_repo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_answers_repository WHERE id = %d", $question['response_repo']));

				if (!empty($theme) && !empty($response_repo)) {
					$question_text = $question['question'];
					try {
						$data = array(
							'text' => $question_text,
							'theme_id' => $theme->id,
							'answer_repo_id' => $response_repo->id,
							'reversed_question' => $question['reversed_question'],
							'no_longer_used' => $question['no_longer_used']
						);

						// If ID is provided and question exists -> UPDATE, otherwise -> INSERT
						if (!empty($question['id'])) {
							$existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_questions WHERE id = %d", $question['id']));
							if ($existing) {
								$wpdb->update($table_name_questions, $data, array('id' => $question['id']));
								$questionsUpdated++;
								continue;
							}
						}

						$wpdb->insert(
							$table_name_questions,
							$data
						);
						$questionsImported++;
					} catch (\Throwable $th) {
						xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - Error inserting question: " . $th->getMessage());
						$questionsNotImported++;
					}

				} else {
					if (empty($theme)) {
                        xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - No theme found for ID: " . $question['id_theme']);
                    }
                    if (empty($response_repo)) {
                        xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - No response repository found for ID: " . $question['response_repo']);
                    }
					$questionsNotImported++;
				}
			}

			// 7/ Return response
			$totalProcessed = $questionsImported + $questionsUpdated;
			if ($questionsNotImported === 0 && $totalProcessed > 0) {
				// Toutes les questions ont été traitées avec succès
				return new \WP_REST_Response(array(
					'imported' => $questionsImported,
					'updated' => $questionsUpdated,
					'errors' => $questionsNotImported
				), 201);
			} else {
				if ($totalProcessed === 0) {
					// Aucune question n'a été importée
					// 500 Internal Server Error
					xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - No questions imported");
					return new \WP_Error('noImported', __('No questions imported', 'QVST'));
				} else {
					// Certaines questions ont été traitées avec succès, d'autres non
					// 206 Partial Content
					xpeapp_log(\Xpeapp_Log_Level::Warn, "POST xpeho/v1/qvst:import - Some questions processed successfully, others not");
					return new \WP_REST_Response(array(
						'imported' => $questionsImported,
						'updated' => $questionsUpdated,
						'errors' => $questionsNotImported
					), 206);
				}
			}
		}
	} catch (\Throwable $th) {
		xpeapp_log(\Xpeapp_Log_Level::Error, "POST xpeho/v1/qvst:import - Error: " . $th->getMessage());
		return new \WP_Error('error', __('Error : ' . $th, 'QVST'));
	}
}	
}
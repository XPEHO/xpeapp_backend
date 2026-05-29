<?php

namespace XpeApp\qvst\questions;

class GetQvstQuestionsByTheme {
    public static function apiGetQvstQuestionsByThemeId(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);

        // Utiliser la classe $wpdb pour effectuer une requête SQL
        global $wpdb;

        // Nom des tables personnalisées
        $table_name_questions = $wpdb->prefix . 'qvst_questions';
        $table_name_answers = $wpdb->prefix . 'qvst_answers';
        $table_name_theme = $wpdb->prefix . 'qvst_theme';
        $table_name_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';

        $params = $request->get_params();
        $theme_id = isset($params['id']) ? intval($params['id']) : 0;
        if (empty($theme_id)) {
            return new \WP_Error('noID', __('No ID', 'QVST'));
        }

        // Vérifier si on doit inclure les questions no_longer_used (par défaut : false)
        $includeNoLongerUsed = filter_var($request->get_param('include_no_longer_used'), FILTER_VALIDATE_BOOLEAN);
        $page = $request->get_param('page');
        $perPage = max(1, intval($request->get_param('per_page') ?: 10));
        $shouldPaginate = $page !== null && $page !== '';

        $queryAnswer = "
            SELECT
                theme.id as theme_id,
                theme.name as theme_name,
                question.id as question_id,
                question.text as text_question,
                question.answer_repo_id,
                answers.id as answer_id,
                answers.name,
                answers.value,
                COALESCE(cq.num_occurrences, 0) as numberAsked,
                COALESCE(question.reversed_question, 0) as reversed_question,
                COALESCE(question.no_longer_used, 0) as no_longer_used
            FROM {$table_name_answers} answers
            INNER JOIN {$table_name_questions} question ON question.answer_repo_id = answers.answer_repo_id
            INNER JOIN {$table_name_theme} theme ON question.theme_id = theme.id
            LEFT JOIN (
                SELECT question_id, COUNT(campaign_id) as num_occurrences
                FROM {$table_name_campaign_questions}
                GROUP BY question_id
            ) cq ON question.id = cq.question_id
            WHERE theme.id = %d
        ";

        if (!$includeNoLongerUsed) {
            $queryAnswer .= " AND COALESCE(question.no_longer_used, 0) = 0";
        }

        $resultsAnswer = array();
        if ($shouldPaginate) {
            $offset = (max(1, intval($page)) - 1) * $perPage;
            // Récupérer les IDs des questions de la page courante
            $idsQuery = "
                SELECT question.id
                FROM {$table_name_questions} question
                INNER JOIN {$table_name_theme} theme ON question.theme_id = theme.id
                WHERE theme.id = %d
            ";
            if (!$includeNoLongerUsed) {
                $idsQuery .= " AND COALESCE(question.no_longer_used, 0) = 0";
            }
            $idsQuery .= " GROUP BY question.id, theme.id ORDER BY question.id LIMIT {$perPage} OFFSET {$offset}";
            $questionIds = $wpdb->get_col($wpdb->prepare($idsQuery, $theme_id));

            if (!empty($questionIds)) {
                // Charger toutes les réponses des questions sélectionnées
                $placeholders = implode(',', array_fill(0, count($questionIds), '%d'));
                $resultsQuery = $queryAnswer . " AND question.id IN ($placeholders) ORDER BY question.id, answers.value DESC";
                $resultsAnswer = $wpdb->get_results(call_user_func_array([$wpdb, 'prepare'], array_merge([$resultsQuery, $theme_id], $questionIds)));
            }
        } else {
            // Comportement historique : renvoyer toutes les lignes sans pagination
            $resultsAnswer = $wpdb->get_results($wpdb->prepare($queryAnswer . " ORDER BY question.id, answers.value DESC", $theme_id));
        }

        // Utilisation de la fonction factorisée dans utils.php pour SonarQube
        return formatQvstQuestionsWithAnswers($resultsAnswer, true);
    }
}
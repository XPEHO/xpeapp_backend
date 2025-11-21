<?php

require_once __DIR__ . '/campaign_themes_utils.php';

/**
 * Analyse les questions de la campagne et calcule les statistiques
 */
function analyzeQuestionsData($wpdb, $campaign_id, $table_campaign_questions, $table_questions, $table_theme, $table_answers, $table_campaign_answers)
{
    $questions_analysis = $wpdb->get_results($wpdb->prepare("
        SELECT
            q.id as question_id,
            q.text as question_text,
            t.id as theme_id,
            t.name as theme_name,
            a.id as answer_id,
            a.name as answer_text,
            a.value as answer_value,
            COUNT(ca.id) as count
        FROM $table_campaign_questions cq
        INNER JOIN $table_questions q ON q.id = cq.question_id
        INNER JOIN $table_theme t ON t.id = q.theme_id
        INNER JOIN $table_answers a ON a.answer_repo_id = q.answer_repo_id
        LEFT JOIN $table_campaign_answers ca
            ON ca.campaign_id = %d
            AND ca.question_id = q.id
            AND ca.answer_id = a.id
        WHERE cq.campaign_id = %d
        GROUP BY q.id, a.id
        ORDER BY q.id, a.value DESC
    ", $campaign_id, $campaign_id));

    $questions_data = [];
    $total_questions = 0;
    
    foreach ($questions_analysis as $row) {
        $qid = $row->question_id;
        
        if (!isset($questions_data[$qid])) {
            $questions_data[$qid] = [
                'question_id' => $qid,
                'question_text' => $row->question_text,
                'theme_id' => $row->theme_id,
                'theme_name' => $row->theme_name,
                'answers' => [],
                'total_responses' => 0,
                'weighted_sum' => 0,
                'max_possible_score' => 0
            ];
            $total_questions++;
        }
        
        $count = (int)$row->count;
        $value = (int)$row->answer_value;
        
        $questions_data[$qid]['answers'][] = [
            'answer_id' => $row->answer_id,
            'answer_text' => $row->answer_text,
            'score' => $value,
            'count' => $count
        ];
        
        $questions_data[$qid]['total_responses'] += $count;
        $questions_data[$qid]['weighted_sum'] += ($value * $count);
        
        if ($value > $questions_data[$qid]['max_possible_score']) {
            $questions_data[$qid]['max_possible_score'] = $value;
        }
    }

    // Calculer le pourcentage de satisfaction par question
    foreach ($questions_data as &$question) {
        if ($question['total_responses'] > 0 && $question['max_possible_score'] > 0) {
            $average_score = $question['weighted_sum'] / $question['total_responses'];
            $question['satisfaction_percentage'] = round(($average_score / $question['max_possible_score']) * 100, 2);
            $question['average_score'] = round($average_score, 2);
        } else {
            $question['satisfaction_percentage'] = 0;
            $question['average_score'] = 0;
        }
        
        $question['requires_action'] = $question['satisfaction_percentage'] < 75;
    }
    unset($question);

    return ['questions_data' => $questions_data, 'total_questions' => $total_questions];
}

/**
 * Initialise les données d'un employé
 */
function initializeEmployeeData($group_id)
{
    return [
        'answer_group_id' => $group_id,
        'responses' => [],
        'total_score' => 0,
        'max_possible_score' => 0,
        'response_count' => 0,
        'low_scores_count' => 0,
        'critical_themes' => []
    ];
}

/**
 * Traite une réponse individuelle d'un employé
 */
function processEmployeeAnswer(&$employee_data, $row, $questions_data)
{
    $value = (int)$row->answer_value;
    
    $employee_data['responses'][] = [
        'question_id' => $row->question_id,
        'answer_value' => $value,
        'answer_text' => $row->answer_text
    ];
    
    $employee_data['total_score'] += $value;
    $employee_data['response_count']++;
    
    if ($value <= 2 && isset($questions_data[$row->question_id])) {
        $employee_data['low_scores_count']++;
        $theme_name = $questions_data[$row->question_id]['theme_name'];
        if (!in_array($theme_name, $employee_data['critical_themes'])) {
            $employee_data['critical_themes'][] = $theme_name;
        }
    }
    
    if (isset($questions_data[$row->question_id]['max_possible_score'])) {
        $employee_data['max_possible_score'] += $questions_data[$row->question_id]['max_possible_score'];
    }
}

/**
 * Analyse les réponses par collaborateur et calcule les scores de risque
 */
function analyzeEmployeesData($wpdb, $campaign_id, $questions_data, $table_campaign_answers, $table_answers, $table_open_answers)
{
    $employee_answers = $wpdb->get_results($wpdb->prepare("
        SELECT
            ca.answer_group_id,
            ca.question_id,
            a.value as answer_value,
            a.name as answer_text
        FROM $table_campaign_answers ca
        INNER JOIN $table_answers a ON a.id = ca.answer_id
        WHERE ca.campaign_id = %d
        ORDER BY ca.answer_group_id, ca.question_id
    ", $campaign_id));

    $open_answers = $wpdb->get_results($wpdb->prepare("
        SELECT
            oa.answer_group_id,
            oa.text as open_answer_text
        FROM $table_open_answers oa
        WHERE oa.answer_group_id IN (
            SELECT DISTINCT answer_group_id
            FROM $table_campaign_answers
            WHERE campaign_id = %d
        )
    ", $campaign_id));

    $employees_data = [];
    foreach ($employee_answers as $row) {
        $group_id = $row->answer_group_id;
        
        if (!isset($employees_data[$group_id])) {
            $employees_data[$group_id] = initializeEmployeeData($group_id);
        }
        
        processEmployeeAnswer($employees_data[$group_id], $row, $questions_data);
    }

    foreach ($open_answers as $open) {
        if (isset($employees_data[$open->answer_group_id])) {
            $employees_data[$open->answer_group_id]['open_answer'] = $open->open_answer_text;
        }
    }

    return $employees_data;
}

/**
 * Calcule les scores de risque et identifie les collaborateurs à risque
 */
function calculateRiskScores($employees_data)
{
    $at_risk_employees = [];
    $satisfaction_scores = [];
    
    foreach ($employees_data as &$employee) {
        if ($employee['response_count'] > 0 && $employee['max_possible_score'] > 0) {
            $employee['average_score'] = round($employee['total_score'] / $employee['response_count'], 2);
            $employee['satisfaction_percentage'] = round(($employee['total_score'] / $employee['max_possible_score']) * 100, 2);


            $low_score_ratio = $employee['low_scores_count'] / $employee['response_count'];
            $risk_score = (10 - ($employee['satisfaction_percentage'] / 10)) + (10 * $low_score_ratio);
            $risk_score = max(0, min(10, $risk_score));
            $employee['risk_score'] = round($risk_score, 2);
            $satisfaction_scores[] = $employee['satisfaction_percentage'];

            if ($employee['satisfaction_percentage'] < 75 || $employee['risk_score'] > 3) {
                $at_risk_employees[] = [
                    'anonymous_user_id' => $employee['answer_group_id'],
                    'satisfaction_percentage' => $employee['satisfaction_percentage'],
                    'risk_score' => $employee['risk_score'],
                    'low_scores_count' => $employee['low_scores_count'],
                    'total_responses' => $employee['response_count'],
                    'critical_themes' => $employee['critical_themes'],
                    'open_answer' => $employee['open_answer'] ?? null
                ];
            }
        }
    }
    unset($employee);

    usort($at_risk_employees, function ($a, $b) {
        return $b['risk_score'] <=> $a['risk_score'];
    });

    return ['at_risk_employees' => $at_risk_employees, 'satisfaction_scores' => $satisfaction_scores];
}

/**
 * Calcule la distribution globale des réponses
 */
function calculateGlobalDistribution($questions_data)
{
    $global_distribution = [];
    foreach ($questions_data as $question) {
        foreach ($question['answers'] as $answer) {
            $score = $answer['score'];
            if (!isset($global_distribution[$score])) {
                $global_distribution[$score] = 0;
            }
            $global_distribution[$score] += $answer['count'];
        }
    }
    
    $global_distribution_array = [];
    foreach ($global_distribution as $score => $count) {
        $global_distribution_array[] = [
            'score' => $score,
            'count' => $count
        ];
    }
    usort($global_distribution_array, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $global_distribution_array;
}

/**
 * Calcule l'analyse par thème
 */
function calculateThemesAnalysis($questions_data)
{
    $themes_stats = [];
    
    foreach ($questions_data as $question) {
        $theme_id = $question['theme_id'];
        
        if (!isset($themes_stats[$theme_id])) {
            $themes_stats[$theme_id] = [
                'theme_id' => $theme_id,
                'theme_name' => $question['theme_name'],
                'total_weighted_sum' => 0,
                'total_responses' => 0,
                'total_max_score' => 0,
                'low_score_questions_count' => 0,
                'questions_count' => 0
            ];
        }
        
        $themes_stats[$theme_id]['total_weighted_sum'] += $question['weighted_sum'];
        $themes_stats[$theme_id]['total_responses'] += $question['total_responses'];
        $themes_stats[$theme_id]['total_max_score'] += ($question['max_possible_score'] * $question['total_responses']);
        $themes_stats[$theme_id]['questions_count']++;
        
        if ($question['requires_action']) {
            $themes_stats[$theme_id]['low_score_questions_count']++;
        }
    }
    
    $themes_analysis = [];
    foreach ($themes_stats as $theme) {
        $average_score = 0;
        $satisfaction_percentage = 0;
        
        if ($theme['total_responses'] > 0 && $theme['total_max_score'] > 0) {
            $average_score = round($theme['total_weighted_sum'] / $theme['total_responses'], 2);
            $satisfaction_percentage = round(($theme['total_weighted_sum'] / $theme['total_max_score']) * 100, 2);
        }
        
        $themes_analysis[] = [
            'theme_id' => $theme['theme_id'],
            'theme_name' => $theme['theme_name'],
            'average_score' => $average_score,
            'satisfaction_percentage' => $satisfaction_percentage,
            'requires_action' => $satisfaction_percentage < 75,
            'low_score_questions_count' => $theme['low_score_questions_count'],
            'total_questions' => $theme['questions_count']
        ];
    }
    
    usort($themes_analysis, function ($a, $b) {
        return $a['satisfaction_percentage'] <=> $b['satisfaction_percentage'];
    });

    return $themes_analysis;
}

function apiGetCampaignAnalysis(WP_REST_Request $request)
{
    xpeapp_log_request($request);

    global $wpdb;

    $params = $request->get_params();
    $campaign_id = $params['id'];

    $result = [];
    // Toujours retourner un tableau, même en cas d'erreur ou campagne non trouvée
    if (empty($campaign_id)) {
        xpeapp_log(Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - No parameters");
        $result = [];
    } else {
        try {
            $table_campaign = $wpdb->prefix . 'qvst_campaign';
            $table_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
            $table_campaign_questions = $wpdb->prefix . 'qvst_campaign_questions';
            $table_answers = $wpdb->prefix . 'qvst_answers';
            $table_questions = $wpdb->prefix . 'qvst_questions';
            $table_theme = $wpdb->prefix . 'qvst_theme';
            $table_open_answers = $wpdb->prefix . 'qvst_open_answers';

            // Vérifier que la campagne existe (requête préparée)
            $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_campaign WHERE id=%d", $campaign_id));
            if (empty($campaign)) {
                xpeapp_log(Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - No campaign found for id $campaign_id");
                $result = [];
            } else {
                // Récupérer les thèmes de la campagne
                $themes_raw = getThemesForCampaign($campaign_id);
                $themes = [];
                foreach ($themes_raw as $theme) {
                    $themes[] = [
                        'theme_id' => $theme->id,
                        'theme_name' => $theme->name
                    ];
                }

                // Analyse par question
                $questions_result = analyzeQuestionsData(
                    $wpdb,
                    $campaign_id,
                    $table_campaign_questions,
                    $table_questions,
                    $table_theme,
                    $table_answers,
                    $table_campaign_answers
                );
                $questions_data = $questions_result['questions_data'];
                $total_questions = $questions_result['total_questions'];

                // Analyse par collaborateur
                $employees_data = analyzeEmployeesData(
                    $wpdb,
                    $campaign_id,
                    $questions_data,
                    $table_campaign_answers,
                    $table_answers,
                    $table_open_answers
                );

                // Calcul des scores de risque
                $risk_result = calculateRiskScores($employees_data);
                $at_risk_employees = $risk_result['at_risk_employees'];
                $satisfaction_scores = $risk_result['satisfaction_scores'];

                // Statistiques globales
                $total_respondents = count($employees_data);
                $average_satisfaction = $total_respondents > 0
                    ? round(array_sum($satisfaction_scores) / count($satisfaction_scores), 2)
                    : 0;

                // Questions nécessitant une action
                $questions_requiring_action = array_values(array_filter($questions_data, function ($q) {
                    return $q['requires_action'];
                }));

                // Distribution globale
                $global_distribution_array = calculateGlobalDistribution($questions_data);

                // Analyse par thème
                $themes_analysis = calculateThemesAnalysis($questions_data);

                // Helper to always return a list (never null)
                $ensure_list = function($v) {
                    return is_array($v) ? array_values($v) : [];
                };

                // Helper to always return a float (double)
                $ensure_double = function($v) {
                    return is_numeric($v) ? (float)$v : 0.0;
                };

                // Always include all expected keys, even if empty
                $result = [
                    'campaign_id' => (int)$campaign_id,
                    'campaign_name' => $campaign->name,
                    'campaign_status' => $campaign->status,
                    'start_date' => $campaign->start_date,
                    'end_date' => $campaign->end_date,
                    'themes' => $ensure_list($themes),
                    'global_stats' => [
                        'total_respondents' => (int)$total_respondents,
                        'total_questions' => (int)$total_questions,
                        'average_satisfaction' => $ensure_double($average_satisfaction),
                        'requires_action' => $ensure_double($average_satisfaction) < 75.0,
                        'at_risk_count' => (int)count($at_risk_employees)
                    ],
                    'global_distribution' => $ensure_list($global_distribution_array),
                    'themes_analysis' => $ensure_list($themes_analysis),
                    'questions_analysis' => $ensure_list(array_values($questions_data)),
                    'questions_requiring_action' => $ensure_list($questions_requiring_action),
                    'at_risk_employees' => $ensure_list($at_risk_employees)
                ];
            }
        } catch (\Throwable $th) {
            xpeapp_log(Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - Error: " . $th->getMessage());
            $result = [];
        }
    }
    return $result;
}

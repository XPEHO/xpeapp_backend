<?php
namespace XpeApp\qvst\campaign;

include_once __DIR__ . '/../../logging.php';
require_once __DIR__ . '/GetStatsOfCampaign.php';

/**
 * Calcule la satisfaction par question et identifie les questions sous le seuil d'alerte.
 *
 * @param array<string, mixed> $stats_data Donnees de stats de la campagne.
 * @return array<string, mixed>
 */
function calculateQuestionSatisfaction($stats_data)
{
    $questions_analysis = [];
    $questions_requiring_action = [];
    $total_satisfaction = 0;

    foreach ($stats_data['questions'] as $question) {
        $is_reversed = isset($question->reversed_question) && (bool)$question->reversed_question;
        list($min_value, $max_value) = getMinMaxAnswerValues($question->answers);
        list($total_responses, $satisfied_count) = getSatisfactionCounts($question->answers, $is_reversed, $min_value, $max_value);

        $satisfaction_percentage = $total_responses > 0
            ? round(($satisfied_count / $total_responses) * 100, 2)
            : 0;

        $question_data = [
            'question_id' => $question->question_id,
            'question_text' => $question->question,
            'satisfaction_percentage' => $satisfaction_percentage,
            'total_responses' => $total_responses,
            'requires_action' => $satisfaction_percentage < 75,
            'reversed_question' => $is_reversed,
            'answers' => $question->answers
        ];

        $questions_analysis[] = $question_data;
        $total_satisfaction += $satisfaction_percentage;

        if ($question_data['requires_action']) {
            $questions_requiring_action[] = $question_data;
        }
    }

    return [
        'questions_analysis' => $questions_analysis,
        'questions_requiring_action' => $questions_requiring_action,
        'total_satisfaction' => $total_satisfaction
    ];
}

/**
 * Retourne les bornes min/max d'une echelle de reponses.
 *
 * @param array<int, object> $answers Liste des reponses possibles.
 * @return array{0:int,1:int}
 */
function getMinMaxAnswerValues($answers)
{
    // Utiliser une echelle fixe : min = 1, max = 5
    // Cela garantit un calcul de satisfaction cohérent independamment
    // des valeurs declarees dans le référentiel de réponses.
    return [1, 5];
}

/**
 * Compte le nombre total de reponses et le nombre de reponses satisfaites.
 *
 * Pour une question inversee, la valeur est remappee sur la meme echelle
 * afin d'appliquer une regle unique de satisfaction.
 *
 * @param array<int, object> $answers
 * @param bool $is_reversed
 * @param int $min_value
 * @param int $max_value
 * @return array{0:int,1:int}
 */
function getSatisfactionCounts($answers, $is_reversed, $min_value, $max_value)
{
    // Forcer l'echelle de notation a 1..5 pour le calcul
    $min_value = 1;
    $max_value = 5;

    $total_responses = 0;
    $satisfied_count = 0;
    foreach ($answers as $answer) {
        $count = (int)$answer->numberAnswered;
        $value = (int)$answer->value;
        if ($is_reversed) {
            $value = $max_value + $min_value - $value;
        }
        $total_responses += $count;
        // Seuil metier: les scores >= 4 sont consideres comme satisfaits.
        if ($value >= 4) {
            $satisfied_count += $count;
        }
    }
    return [$total_responses, $satisfied_count];
}

/**
 * Construit les donnees anonymes par repondant et detecte les profils a risque.
 *
 * @param \wpdb $wpdb
 * @param int|string $campaign_id
 * @return array<string, mixed>
 */
function analyzeEmployeesAtRisk($wpdb, $campaign_id)
{
    $table_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
    $table_answers = $wpdb->prefix . 'qvst_answers';
    $table_open_answers = $wpdb->prefix . 'qvst_open_answers';
    $table_questions = $wpdb->prefix . 'qvst_questions';

    $employee_answers = $wpdb->get_results($wpdb->prepare("
        SELECT
            ca.answer_group_id,
            ca.question_id,
            COALESCE(ca.answer_value, a.value) as answer_value,
            COALESCE(q.reversed_question, 0) as reversed_question,
            repo_stats.min_value,
            repo_stats.max_value
        FROM $table_campaign_answers ca
        INNER JOIN $table_answers a ON a.id = ca.answer_id
        INNER JOIN $table_questions q ON q.id = ca.question_id
        INNER JOIN (
            SELECT answer_repo_id, MIN(value) as min_value, MAX(value) as max_value
            FROM $table_answers
            GROUP BY answer_repo_id
        ) repo_stats ON repo_stats.answer_repo_id = q.answer_repo_id
        WHERE ca.campaign_id = %d
    ", $campaign_id));

    $open_answers = $wpdb->get_results($wpdb->prepare("
        SELECT answer_group_id, text as open_answer_text
        FROM $table_open_answers
        WHERE answer_group_id IN (
            SELECT DISTINCT answer_group_id
            FROM $table_campaign_answers
            WHERE campaign_id = %d
        )
    ", $campaign_id));

    $employees_data = [];
    foreach ($employee_answers as $row) {
        updateEmployeeData($employees_data, $row);
    }

    foreach ($open_answers as $open) {
        if (isset($employees_data[$open->answer_group_id])) {
            $employees_data[$open->answer_group_id]['open_answer'] = $open->open_answer_text;
        }
    }

    $at_risk_employees = getAtRiskEmployees($employees_data);

    return [
        'employees_data' => $employees_data,
        'at_risk_employees' => $at_risk_employees
    ];
}

/**
 * Calcule le pourcentage de satisfaction d'un repondant.
 *
 * @param array<string, mixed> $employee
 * @return float
 */
function getEmployeeSatisfaction($employee)
{
    if ($employee['total_responses'] > 0) {
        return round(($employee['satisfied_count'] / $employee['total_responses']) * 100, 2);
    }
    return 0;
}

/**
 * Filtre les repondants sous le seuil de satisfaction pour produire la liste a risque.
 *
 * @param array<string, array<string, mixed>> $employees_data
 * @return array<int, array<string, mixed>>
 */
function getAtRiskEmployees($employees_data)
{
    $at_risk_employees = [];
    foreach ($employees_data as $group_id => $employee) {
        $satisfaction = getEmployeeSatisfaction($employee);
        if ($employee['total_responses'] > 0 && $satisfaction < 75) {
            $at_risk_employees[] = [
                'anonymous_user_id' => $group_id,
                'satisfaction_percentage' => $satisfaction,
                'total_responses' => $employee['total_responses'],
                'open_answer' => $employee['open_answer']
            ];
        }
    }
    return $at_risk_employees;
}

/**
 * Agrege la reponse d'une question dans la structure employee_data.
 *
 * @param array<string, array<string, mixed>> $employees_data
 * @param object $row
 * @return void
 */
function updateEmployeeData(&$employees_data, $row)
{
    $group_id = $row->answer_group_id;
    $value = (int)$row->answer_value;
    if ((bool)$row->reversed_question) {
        // Utiliser l'echelle fixe 1..5 pour normaliser les questions inversees
        $value = 5 + 1 - $value;
    }
    if (!isset($employees_data[$group_id])) {
        $employees_data[$group_id] = [
            'total_responses' => 0,
            'satisfied_count' => 0,
            'open_answer' => null
        ];
    }
    $employees_data[$group_id]['total_responses']++;
    // Seuil metier: les scores >= 4 sont consideres comme satisfaits.
    if ($value >= 4) {
        $employees_data[$group_id]['satisfied_count']++;
    }
}

/**
 * Calcule la distribution globale des reponses (score -> nombre de reponses).
 *
 * @param array<int, array<string, mixed>> $questions_analysis
 * @return array<int, array{score:mixed,count:mixed}>
 */
function calculateGlobalDistribution($questions_analysis)
{
    $global_distribution = [];
    foreach ($questions_analysis as $question) {
        foreach ($question['answers'] as $answer) {
            $score = $answer->value;
            if (!isset($global_distribution[$score])) {
                $global_distribution[$score] = 0;
            }
            $global_distribution[$score] += $answer->numberAnswered;
        }
    }
    
    $global_distribution_array = [];
    foreach ($global_distribution as $score => $count) {
        $global_distribution_array[] = ['score' => $score, 'count' => $count];
    }
    usort($global_distribution_array, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $global_distribution_array;
}


/**
 * Endpoint d'analyse de campagne QVST.
 *
 * Orchestre la recuperation des stats, le calcul de satisfaction, l'identification
 * des collaborateurs a risque et la construction de la reponse agregée.
 */
class GetCampaignAnalysis {
    /**
     * Construit la reponse d'analyse complete pour une campagne.
     *
     * @param \WP_REST_Request $request
     * @return array<string, mixed>
     */
    public static function apiGetCampaignAnalysis(\WP_REST_Request $request)
    {
        xpeapp_log_request($request);
        global $wpdb;
        $params = $request->get_params();
        $campaign_id = $params['id'] ?? null;
        $response = [];

        if (empty($campaign_id)) {
            xpeapp_log(\Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - No parameters");
        } else {
            try {
                $stats_request = new \WP_REST_Request('GET', '/qvst/campaigns/{id}/stats');
                $stats_request->set_param('id', $campaign_id);
                $stats_response = GetStatsOfCampaign::apiGetQvstStatsByCampaignId($stats_request);
                if (is_wp_error($stats_response)) {
                    xpeapp_log(\Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - Stats error: " . $stats_response->get_error_message());
                } else {
                    $stats_data = $stats_response->get_data();
                    $question_results = calculateQuestionSatisfaction($stats_data);
                    $employee_results = analyzeEmployeesAtRisk($wpdb, $campaign_id);
                    $global_distribution_array = calculateGlobalDistribution($question_results['questions_analysis']);

                    $total_questions = count($question_results['questions_analysis']);
                    $average_satisfaction = $total_questions > 0
                        ? round($question_results['total_satisfaction'] / $total_questions, 2)
                        : 0;

                    $response = [
                        'campaign_id' => (int)$campaign_id,
                        'campaign_name' => $stats_data['campaignName'],
                        'campaign_status' => $stats_data['campaignStatus'],
                        'start_date' => $stats_data['startDate'],
                        'end_date' => $stats_data['endDate'],
                        'themes' => $stats_data['themes'],
                        'global_stats' => [
                            'total_respondents' => count($employee_results['employees_data']),
                            'total_questions' => $total_questions,
                            'average_satisfaction' => $average_satisfaction,
                            // Campagne marquee "a actionner" si la moyenne est sous 75%.
                            'requires_action' => $average_satisfaction < 75.0,
                            'at_risk_count' => count($employee_results['at_risk_employees'])
                        ],
                        'global_distribution' => $global_distribution_array,
                        'questions_analysis' => $question_results['questions_analysis'],
                        'questions_requiring_action' => array_values($question_results['questions_requiring_action']),
                        'at_risk_employees' => $employee_results['at_risk_employees']
                    ];
                }
            } catch (\Throwable $th) {
                xpeapp_log(\Xpeapp_Log_Level::Error, "GET xpeho/v1/qvst/campaigns/{id}:analysis - Error: " . $th->getMessage());
            }
        }
        return $response;
    }
}
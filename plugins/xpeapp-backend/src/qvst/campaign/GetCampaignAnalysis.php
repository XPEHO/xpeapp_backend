<?php
namespace XpeApp\qvst\campaign;

include_once __DIR__ . '/../../logging.php';
require_once __DIR__ . '/GetStatsOfCampaign.php';

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

function getMinMaxAnswerValues($answers)
{
    $min_value = PHP_INT_MAX;
    $max_value = PHP_INT_MIN;
    foreach ($answers as $answer) {
        $value = (int)$answer->value;
        if ($value < $min_value) {
            $min_value = $value;
        }
        if ($value > $max_value) {
            $max_value = $value;
        }
    }
    return [$min_value, $max_value];
}

function getSatisfactionCounts($answers, $is_reversed, $min_value, $max_value)
{
    $total_responses = 0;
    $satisfied_count = 0;
    foreach ($answers as $answer) {
        $count = (int)$answer->numberAnswered;
        $value = (int)$answer->value;
        if ($is_reversed) {
            $value = $max_value + $min_value - $value;
        }
        $total_responses += $count;
        if ($value >= 4) {
            $satisfied_count += $count;
        }
    }
    return [$total_responses, $satisfied_count];
}

function analyzeEmployeesAtRisk($wpdb, $campaign_id)
{
    $table_campaign_answers = $wpdb->prefix . 'qvst_campaign_answers';
    $table_answers = $wpdb->prefix . 'qvst_answers';
    $table_open_answers = $wpdb->prefix . 'qvst_open_answers';
    $table_questions = $wpdb->prefix . 'qvst_questions';

    // Get answers with reversed_question info and min/max values per answer repo
    // Use snapshot values (answer_value) if available, fallback to current values
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
        $group_id = $row->answer_group_id;
        $value = (int)$row->answer_value;
        
        // Invert value for reversed questions
        if ((bool)$row->reversed_question) {
            $value = (int)$row->max_value + (int)$row->min_value - $value;
        }
        
        if (!isset($employees_data[$group_id])) {
            $employees_data[$group_id] = [
                'total_responses' => 0,
                'satisfied_count' => 0,
                'open_answer' => null
            ];
        }
        
        $employees_data[$group_id]['total_responses']++;
        if ($value >= 4) {
            $employees_data[$group_id]['satisfied_count']++;
        }
    }

    foreach ($open_answers as $open) {
        if (isset($employees_data[$open->answer_group_id])) {
            $employees_data[$open->answer_group_id]['open_answer'] = $open->open_answer_text;
        }
    }

    $at_risk_employees = [];
    foreach ($employees_data as $group_id => $employee) {
        if ($employee['total_responses'] > 0) {
            $satisfaction = round(($employee['satisfied_count'] / $employee['total_responses']) * 100, 2);
            
            if ($satisfaction < 75) {
                $at_risk_employees[] = [
                    'anonymous_user_id' => $group_id,
                    'satisfaction_percentage' => $satisfaction,
                    'total_responses' => $employee['total_responses'],
                    'open_answer' => $employee['open_answer']
                ];
            }
        }
    }

    return [
        'employees_data' => $employees_data,
        'at_risk_employees' => $at_risk_employees
    ];
}

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


class GetCampaignAnalysis {
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
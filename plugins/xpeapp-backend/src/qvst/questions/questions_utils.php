<?php
namespace XpeApp\qvst\questions;

/**
 * Retourne les détails et les réponses d'une question (préparé + sécurisé).
 * @param int $question_id
 * @return array ['meta' => stdClass|null, 'answers' => array]
 */
function GetQuestionDetails(int $question_id): array
{
    /** @var wpdb $wpdb */
    global $wpdb;

    $table_name_questions = $wpdb->prefix . 'qvst_questions';
    $table_name_answers = $wpdb->prefix . 'qvst_answers';
    $table_name_theme = $wpdb->prefix . 'qvst_theme';

    $question_id = intval($question_id);

    $answersSql = "
        SELECT
            answers.id,
            answers.name,
            answers.value
        FROM {$table_name_answers} answers
        INNER JOIN {$table_name_questions} question ON question.answer_repo_id = answers.answer_repo_id
        WHERE question.id = %d
        ORDER BY answers.id
    ";

    $answers = $wpdb->get_results($wpdb->prepare($answersSql, $question_id));

    $metaSql = "
        SELECT
            question.id as id,
            theme.id as theme_id,
            theme.name as theme,
            question.text as question,
            COUNT(answers.id) as numberOfAnswers,
            ROUND(AVG(answers.value)) as averageAnswer,
            MAX(answers.value) AS maxValueAnswer
        FROM {$table_name_answers} answers
        INNER JOIN {$table_name_questions} question ON question.answer_repo_id = answers.answer_repo_id
        INNER JOIN {$table_name_theme} theme ON theme.id = question.theme_id
        WHERE question.id = %d
    ";

    $meta = $wpdb->get_row($wpdb->prepare($metaSql, $question_id));

    return ['meta' => $meta, 'answers' => $answers];
}

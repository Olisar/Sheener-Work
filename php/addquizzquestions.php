<?php
/* File: sheener/php/addquizzquestions.php */

// php/addquizzquestions.php
// Include your Database class
require_once 'database.php';
$db = new Database();
$pdo = $db->getConnection();

// Example data structure; you can load this from a config file or build programmatically
$quizData = [
    [
        'quiz_id' => 14, // Use actual quiz_id
        'questions' => [
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What is the primary purpose of the EHS Audit Procedure?',
                'explanation' => 'The procedure verifies compliance with EHS policies...',
                'points' => 2.00,
                'display_order' => 1,
                'options' => [
                    ['option_text' => 'To verify compliance with EHS Policies...', 'is_correct' => 1, 'display_order' => 1],
                    ['option_text' => 'To schedule employee training sessions', 'is_correct' => 0, 'display_order' => 2],
                    // ... add all options for the question
                ]
            ],
            // ... add all other questions for this quiz
        ]
    ],
    // ... add all quizzes in similar format
];

// Automated insertion
foreach ($quizData as $quiz) {
    foreach ($quiz['questions'] as $q) {
        // Insert question
        $stmt = $pdo->prepare("
            INSERT INTO quiz_questions
                (quiz_id, question_type, question_text, explanation, points, display_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $quiz['quiz_id'],
            $q['question_type'],
            $q['question_text'],
            $q['explanation'],
            $q['points'],
            $q['display_order']
        ]);

        // Get the auto-generated question_id
        $question_id = $pdo->lastInsertId();

        // Insert options for the question
        foreach ($q['options'] as $opt) {
            $stmtOpt = $pdo->prepare("
                INSERT INTO question_options
                    (question_id, option_text, is_correct, display_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmtOpt->execute([
                $question_id,
                $opt['option_text'],
                $opt['is_correct'],
                $opt['display_order']
            ]);
        }
    }
}

?>

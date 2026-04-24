<?php
/* File: sheener/php/save_quiz_questions.php */

// Save quiz questions and their options
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    @ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'POST method required'], 405);
}

try {
    $pdo = (new Database())->getConnection();
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['quiz_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid request data. Missing quiz_id.'], 400);
    }
    
    $quizId = intval($data['quiz_id']);
    $questions = $data['questions'] ?? [];
    
    if ($quizId <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid quiz_id'], 400);
    }
    
    // Verify quiz exists
    $stmt = $pdo->prepare('SELECT id FROM quizzes WHERE id = ?');
    $stmt->execute([$quizId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(['success' => false, 'error' => 'Quiz not found'], 404);
    }
    
    $pdo->beginTransaction();
    
    $savedQuestions = 0;
    $savedOptions = 0;
    $errors = [];
    
    foreach ($questions as $qIndex => $question) {
        try {
            $questionText = trim($question['question_text'] ?? '');
            if (empty($questionText)) {
                continue; // Skip empty questions
            }
            
            $questionId = isset($question['question_id']) && $question['question_id'] > 0 
                ? intval($question['question_id']) 
                : null;
            
            $options = $question['options'] ?? [];
            $correctIndex = isset($question['correct_index']) ? intval($question['correct_index']) : -1;
            
            // Insert or update question
            if ($questionId) {
                // Update existing question
                $stmt = $pdo->prepare('
                    UPDATE quiz_questions 
                    SET question_text = ?, display_order = ?
                    WHERE id = ? AND quiz_id = ?
                ');
                $stmt->execute([
                    $questionText,
                    $qIndex + 1,
                    $questionId,
                    $quizId
                ]);
            } else {
                // Insert new question
                $stmt = $pdo->prepare('
                    INSERT INTO quiz_questions 
                    (quiz_id, question_type, question_text, points, display_order, active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $quizId,
                    'multiple_choice',
                    $questionText,
                    1.00,
                    $qIndex + 1,
                    1
                ]);
                $questionId = (int)$pdo->lastInsertId();
            }
            
            $savedQuestions++;
            
            // Handle options
            // First, get existing options for this question
            $stmt = $pdo->prepare('SELECT id FROM question_options WHERE question_id = ? ORDER BY display_order, id');
            $stmt->execute([$questionId]);
            $existingOptionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete options that are no longer in the form
            $optionIdsToKeep = [];
            foreach ($options as $opt) {
                if (isset($opt['option_id']) && $opt['option_id'] > 0) {
                    $optionIdsToKeep[] = intval($opt['option_id']);
                }
            }
            
            if (!empty($existingOptionIds)) {
                $idsToDelete = array_diff($existingOptionIds, $optionIdsToKeep);
                if (!empty($idsToDelete)) {
                    $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                    $stmt = $pdo->prepare("DELETE FROM question_options WHERE id IN ($placeholders)");
                    $stmt->execute($idsToDelete);
                }
            }
            
            // Insert or update options
            foreach ($options as $optIndex => $option) {
                $optionText = trim($option['option_text'] ?? '');
                if (empty($optionText)) {
                    continue; // Skip empty options
                }
                
                $isCorrect = ($optIndex === $correctIndex) ? 1 : 0;
                $optionId = isset($option['option_id']) && $option['option_id'] > 0 
                    ? intval($option['option_id']) 
                    : null;
                
                if ($optionId) {
                    // Update existing option
                    $stmt = $pdo->prepare('
                        UPDATE question_options 
                        SET option_text = ?, is_correct = ?, display_order = ?
                        WHERE id = ? AND question_id = ?
                    ');
                    $stmt->execute([
                        $optionText,
                        $isCorrect,
                        $optIndex + 1,
                        $optionId,
                        $questionId
                    ]);
                } else {
                    // Insert new option
                    $stmt = $pdo->prepare('
                        INSERT INTO question_options 
                        (question_id, option_text, is_correct, display_order)
                        VALUES (?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $questionId,
                        $optionText,
                        $isCorrect,
                        $optIndex + 1
                    ]);
                }
                $savedOptions++;
            }
            
        } catch (Exception $e) {
            $errors[] = "Question " . ($qIndex + 1) . ": " . $e->getMessage();
        }
    }
    
    $pdo->commit();
    
    sendJsonResponse([
        'success' => true,
        'message' => "Saved $savedQuestions questions and $savedOptions options",
        'questions_saved' => $savedQuestions,
        'options_saved' => $savedOptions,
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
}


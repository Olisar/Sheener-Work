<?php
/* File: sheener/php/get_quiz_questions.php */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

header('Content-Type: application/json');
include 'database.php';

function jsonResponse($data, $code = 200) {
    @ob_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'GET method required'], 405);
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()], 500);
}

$quiz_id = $_GET['quiz_id'] ?? null;
$doc_version_id = $_GET['doc_version_id'] ?? null;

try {
    // Resolve quiz_id from document version if provided
    if ($doc_version_id) {
        if (!is_numeric($doc_version_id)) {
            jsonResponse(['success' => false, 'error' => 'Invalid document version ID format'], 400);
        }
        
        // Find quiz by doc_version_id (quizzes table has doc_version_id, not documentversions)
        $stmt = $pdo->prepare('SELECT id FROM quizzes WHERE doc_version_id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$doc_version_id]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quiz) {
            jsonResponse(['success' => false, 'error' => 'No active quiz found for this document version'], 404);
        }
        $quiz_id = $quiz['id'];
    }

    // Validate quiz_id
    if (!$quiz_id || !is_numeric($quiz_id)) {
        jsonResponse(['success' => false, 'error' => 'Valid quiz_id is required'], 400);
    }

    // Verify quiz exists, is active, and get its details
    $stmt = $pdo->prepare('SELECT id, title, passing_score FROM quizzes WHERE id = ? AND active = 1');
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        jsonResponse(['success' => false, 'error' => 'Quiz not found, inactive, or access denied'], 404);
    }

    // Get active questions that have at least 2 options
    // Use DISTINCT on question_text to prevent duplicate questions with same text
    $stmt = $pdo->prepare('
        SELECT qq.id, qq.question_text, qq.display_order
        FROM quiz_questions qq
        INNER JOIN question_options qo ON qq.id = qo.question_id
        WHERE qq.quiz_id = ? AND qq.active = 1
        GROUP BY qq.id, qq.question_text, qq.display_order
        HAVING COUNT(DISTINCT qo.id) >= 2
        ORDER BY qq.display_order, qq.id
    ');
    $stmt->execute([$quiz_id]);
    $allQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remove duplicate questions by question_text (keep first occurrence)
    $uniqueQuestions = [];
    $seenTexts = [];
    foreach ($allQuestions as $q) {
        $textKey = md5(strtolower(trim($q['question_text'])));
        if (!isset($seenTexts[$textKey])) {
            $seenTexts[$textKey] = true;
            $uniqueQuestions[] = $q;
        }
    }

    // Check for management mode
    $manage_mode = isset($_GET['manage_mode']) && $_GET['manage_mode'] == '1';

    if (!$manage_mode) {
        if (count($uniqueQuestions) < 5) {
            jsonResponse(['success' => false, 'error' => 'Quiz must have at least 5 unique active questions with 2+ options each'], 500);
        }

        // Randomly select 5 questions
        $questions = getRandomQuestions($uniqueQuestions, 5);
    } else {
        // In manage mode, return all valid unique questions
        $questions = $uniqueQuestions;
    }

    // Fetch all options for selected questions in a single query
    $questionIds = array_column($questions, 'id');
    
    if (empty($questionIds)) {
        // In manage mode, it's possible to have 0 questions if none are added yet or none active
        if ($manage_mode) {
             jsonResponse([
                'success' => true,
                'quiz_id' => (int)$quiz_id,
                'quiz_title' => $quiz['title'],
                'pass_threshold' => round((float)($quiz['passing_score'] ?? 80.0), 2),
                'questions' => []
            ]);
        }
        jsonResponse(['success' => false, 'error' => 'No questions available'], 500);
    }
    
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT question_id, id, option_text, display_order, is_correct
        FROM question_options 
        WHERE question_id IN ($placeholders) 
        ORDER BY question_id, display_order, id
    ");
    $stmt->execute($questionIds);
    $allOptionsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group options by question_id and deduplicate by option_text
    $allOptions = [];
    $seenOptionTexts = []; // Track seen option texts per question
    
    foreach ($allOptionsRaw as $opt) {
        $qId = $opt['question_id'];
        $textKey = strtolower(trim($opt['option_text']));
        $optionKey = $qId . '|' . $textKey;
        
        // Only add if we haven't seen this option text for this question
        // In manage mode, we might want to see duplicates to fix them? 
        // But keeping dedupe logic for consistency for now
        if (!isset($seenOptionTexts[$optionKey])) {
            $seenOptionTexts[$optionKey] = true;
            if (!isset($allOptions[$qId])) {
                $allOptions[$qId] = [];
            }
            $allOptions[$qId][] = $opt;
        }
    }

    // Attach and shuffle options for each question
    foreach ($questions as &$q) {
        $options = $allOptions[$q['id']] ?? [];
        
        // Ensure we have at least 2 options
        if (count($options) < 2 && !$manage_mode) {
            error_log("Warning: Question {$q['id']} has less than 2 unique options");
        }
        
        // Shuffle options to randomize answer positions (ONLY if not managing)
        if (!$manage_mode) {
            shuffleOptions($options);
        }
        
        // Format options for client consumption
        $q['options'] = array_map(function($opt) use ($manage_mode) {
            $formatted = [
                'id' => (int)$opt['id'],
                'option_text' => $opt['option_text']
            ];
            
            // Include is_correct answer flag only in manage mode
            if ($manage_mode) {
                $formatted['is_correct'] = (int)$opt['is_correct'];
            }
            
            return $formatted;
        }, $options);
        
        // Clean up internal fields
        unset($q['display_order']);
    }

    // Return complete response
    jsonResponse([
        'success' => true,
        'quiz_id' => (int)$quiz_id,
        'quiz_title' => $quiz['title'],
        'pass_threshold' => round((float)($quiz['passing_score'] ?? 80.0), 2),
        'questions' => $questions
    ]);

} catch (PDOException $e) {
    error_log("get_quiz_questions.php PDOException: " . $e->getMessage() . " | SQL State: " . $e->getCode());
    jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("get_quiz_questions.php Exception: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    jsonResponse(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}

/**
 * Randomly select N questions from a pool
 */
function getRandomQuestions(array $questions, int $limit): array {
    if (count($questions) <= $limit) {
        return $questions;
    }
    
    $keys = array_keys($questions);
    shuffle($keys);
    $keys = array_slice($keys, 0, $limit);
    
    $selected = [];
    foreach ($keys as $key) {
        $selected[] = $questions[$key];
    }
    
    return $selected;
}

/**
 * Shuffle options array using Fisher-Yates algorithm
 */
function shuffleOptions(array &$options): void {
    $count = count($options);
    for ($i = $count - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $tmp = $options[$i];
        $options[$i] = $options[$j];
        $options[$j] = $tmp;
    }
}

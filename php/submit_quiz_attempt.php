<?php
/* File: sheener/php/submit_quiz_attempt.php */

header('Content-Type: application/json');
include 'database.php';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate request
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['answers'], $data['quiz_id'])) {
    jsonResponse(['error' => 'Invalid request: missing required fields (answers, quiz_id)'], 400);
}

// Use person_id = -1 for visitor mode (instead of NULL to avoid DB constraint)
if (!isset($data['person_id']) || $data['person_id'] === null) {
    $data['person_id'] = -1;  // Special ID for visitors
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $pdo->beginTransaction();

    // Get quiz details
    $stmt = $pdo->prepare('SELECT passing_score, doc_version_id FROM quizzes WHERE id = ?');
    $stmt->execute([$data['quiz_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        jsonResponse(['error' => 'Quiz not found'], 404);
    }
    
    $pass_threshold = (float)($quiz['passing_score'] ?? 80);
    $doc_version_id = $quiz['doc_version_id'];

    // Score answers
    $score = 0;
    $total = count($data['answers']);
    
    if ($total === 0) {
        jsonResponse(['error' => 'No answers provided'], 400);
    }
    
    foreach ($data['answers'] as $ans) {
        if (!isset($ans['option_id']) || !isset($ans['question_id'])) {
            error_log("Invalid answer format: " . json_encode($ans));
            continue;
        }
        
        // Ensure IDs are integers
        $optionId = (int)$ans['option_id'];
        $questionId = (int)$ans['question_id'];
        
        if ($optionId <= 0 || $questionId <= 0) {
            error_log("Invalid IDs: option_id={$optionId}, question_id={$questionId}");
            continue;
        }
        
        $stmt = $pdo->prepare('SELECT is_correct FROM question_options WHERE id = ? AND question_id = ?');
        $stmt->execute([$optionId, $questionId]);
        $isCorrect = $stmt->fetchColumn();
        
        if ($isCorrect === false) {
            error_log("Option not found: option_id={$optionId}, question_id={$questionId}");
            // Option doesn't exist, count as wrong
            continue;
        }
        
        if ($isCorrect) {
            $score++;
        }
    }

    // Determine pass/fail
    $percentage = $total ? round(($score / $total) * 100, 2) : 0;
    $passed = $percentage >= $pass_threshold;
    $pass_datetime = $passed ? date('Y-m-d H:i:s') : null;
    $qr_value = $passed ? 'EHS-PASS-' . uniqid() : null;

    // Determine which table to use - check if quiz_attempts exists, otherwise use trainingattempts
    $tableName = 'quiz_attempts';
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'quiz_attempts'");
    if ($tableCheck->rowCount() == 0) {
        $tableName = 'trainingattempts';
    }
    
    // Check which columns exist in the table
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM $tableName")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        error_log("Error checking table columns: " . $e->getMessage());
        jsonResponse(['error' => 'Database table error: ' . $e->getMessage()], 500);
    }
    
    $hasPercentage = in_array('percentage', $columns);
    $hasTotal = in_array('total', $columns);
    $hasAttemptDatetime = in_array('attempt_datetime', $columns);
    $hasStartTime = in_array('start_time', $columns);
    $hasEndTime = in_array('end_time', $columns);
    $hasTrainingAssignmentId = in_array('trainingassignment_id', $columns);
    $hasAttemptNumber = in_array('attempt_number', $columns);
    $hasStatusId = in_array('status_id', $columns);
    
    // Get training assignment ID if needed (always try to get it if we have doc_version_id)
    $trainingAssignmentId = null;
    if ($doc_version_id && isset($data['person_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM training_assignments WHERE person_id = ? AND doc_version_id = ? LIMIT 1');
        $stmt->execute([$data['person_id'], $doc_version_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        $trainingAssignmentId = $assignment ? $assignment['id'] : null;
    }
    
    // Build INSERT statement based on available columns
    $insertColumns = [];
    $insertValues = [];
    
    // Handle different table structures
    if ($tableName === 'trainingattempts') {
        // trainingattempts table structure - trainingassignment_id is REQUIRED
        if (!$hasTrainingAssignmentId || !$trainingAssignmentId) {
            jsonResponse(['error' => 'Training assignment not found. Please ensure the training is assigned first.'], 400);
        }
        
        $insertColumns[] = 'trainingassignment_id';
        $insertValues[] = $trainingAssignmentId;
        
        // attempt_number is REQUIRED
        $attemptNum = 1;
        $stmt = $pdo->prepare('SELECT MAX(attempt_number) FROM trainingattempts WHERE trainingassignment_id = ?');
        $stmt->execute([$trainingAssignmentId]);
        $maxAttempt = $stmt->fetchColumn();
        $attemptNum = $maxAttempt ? $maxAttempt + 1 : 1;
        $insertColumns[] = 'attempt_number';
        $insertValues[] = $attemptNum;
        
        // status_id is REQUIRED - map passed/failed to status_id
        // Typically: 1 = passed, 2 = failed (based on your data showing status_id=2 for 60%)
        if (in_array('status_id', $columns)) {
            $statusId = $passed ? 1 : 2; // 1 = passed, 2 = failed
            $insertColumns[] = 'status_id';
            $insertValues[] = $statusId;
        }
        
        if ($hasStartTime) {
            $insertColumns[] = 'start_time';
            $insertValues[] = date('Y-m-d H:i:s');
        }
        
        if ($hasEndTime) {
            $insertColumns[] = 'end_time';
            $insertValues[] = date('Y-m-d H:i:s');
        }
    } else {
        // quiz_attempts table structure
        $insertColumns[] = 'person_id';
        $insertValues[] = $data['person_id'];
        $insertColumns[] = 'quiz_id';
        $insertValues[] = $data['quiz_id'];
        
        if (in_array('doc_version_id', $columns)) {
            $insertColumns[] = 'doc_version_id';
            $insertValues[] = $doc_version_id;
        }
    }
    
    // Common columns
    if (in_array('score', $columns)) {
        $insertColumns[] = 'score';
        // For trainingattempts, score might be decimal, for quiz_attempts it's int
        $insertValues[] = $tableName === 'trainingattempts' ? $percentage : $score;
    }
    
    if ($hasTotal) {
        $insertColumns[] = 'total';
        $insertValues[] = $total;
    }
    
    if ($hasPercentage) {
        $insertColumns[] = 'percentage';
        $insertValues[] = $percentage;
    }
    
    if (in_array('passed', $columns)) {
        $insertColumns[] = 'passed';
        $insertValues[] = $passed ? 1 : 0;
    }
    
    if ($hasAttemptDatetime) {
        $insertColumns[] = 'attempt_datetime';
        $insertValues[] = date('Y-m-d H:i:s');
    }
    
    if (in_array('pass_datetime', $columns)) {
        $insertColumns[] = 'pass_datetime';
        $insertValues[] = $pass_datetime;
    }
    
    if (in_array('qr_value', $columns)) {
        $insertColumns[] = 'qr_value';
        $insertValues[] = $qr_value;
    }
    
    if (empty($insertColumns)) {
        jsonResponse(['error' => 'No valid columns found for table: ' . $tableName], 500);
    }
    
    $placeholders = implode(',', array_fill(0, count($insertValues), '?'));
    $columnList = implode(', ', $insertColumns);
    
    $stmt = $pdo->prepare("INSERT INTO $tableName ($columnList) VALUES ($placeholders)");
    $stmt->execute($insertValues);

    // Update training assignment status if quiz is passed
    if ($passed && $doc_version_id && isset($data['person_id'])) {
        try {
            // Update training assignment to 'completed' status when quiz is passed
            $updateStmt = $pdo->prepare('
                UPDATE training_assignments 
                SET status = ?, completion_date = NOW()
                WHERE person_id = ? AND doc_version_id = ? AND status != "completed"
            ');
            $updateStmt->execute(['completed', $data['person_id'], $doc_version_id]);
        } catch (Exception $e) {
            // Log but don't fail the quiz submission if assignment update fails
            error_log("Failed to update training assignment: " . $e->getMessage());
        }
    } elseif (!$passed && $doc_version_id && isset($data['person_id'])) {
        // Update to 'in-progress' if they attempted but didn't pass
        try {
            $updateStmt = $pdo->prepare('
                UPDATE training_assignments 
                SET status = ?
                WHERE person_id = ? AND doc_version_id = ? AND status = "pending"
            ');
            $updateStmt->execute(['in-progress', $data['person_id'], $doc_version_id]);
        } catch (Exception $e) {
            error_log("Failed to update training assignment status: " . $e->getMessage());
        }
    }

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'score' => $score,
        'total' => $total,
        'percentage' => $percentage,
        'passed' => $passed,
        'qr_value' => $qr_value,
        'pass_datetime' => $pass_datetime,
        'threshold' => $pass_threshold
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("submit_quiz_attempt.php PDOException: " . $e->getMessage());
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("submit_quiz_attempt.php Exception: " . $e->getMessage());
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}

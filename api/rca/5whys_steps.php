<?php
/* File: sheener/api/rca/5whys_steps.php */

/**
 * 5 Whys Steps API
 * Handles CRUD operations for 5 Whys steps
 */

session_start();
require_once __DIR__ . '/../../php/database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = [];
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $step_id = $_GET['step_id'] ?? $input['step_id'] ?? null;
    $rca_id = $_GET['rca_id'] ?? $input['rca_id'] ?? null;
    
    if ($method === 'POST') {
        // Create new step
        if (!$rca_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'rca_id is required']);
            exit;
        }

        // Ensure parent 5 Whys record exists (protects older RCAs missing initialization)
        $existsStmt = $pdo->prepare("SELECT rca_id FROM rca_5whys WHERE rca_id = :rca_id");
        $existsStmt->execute([':rca_id' => $rca_id]);
        if (!$existsStmt->fetch()) {
            $initStmt = $pdo->prepare("INSERT INTO rca_5whys (rca_id, problem_statement, root_cause_statement) VALUES (:rca_id, '', '')");
            $initStmt->execute([':rca_id' => $rca_id]);
        }
        
        // Get next step number
        $stepNumSql = "SELECT MAX(step_number) as max_step FROM rca_5whys_steps WHERE rca_id = :rca_id";
        $stepNumStmt = $pdo->prepare($stepNumSql);
        $stepNumStmt->execute([':rca_id' => $rca_id]);
        $stepNumResult = $stepNumStmt->fetch(PDO::FETCH_ASSOC);
        $nextStepNumber = ($stepNumResult['max_step'] ?? 0) + 1;
        
        $question = $input['why_question'] ?? $input['question'] ?? '';
        $sql = "INSERT INTO rca_5whys_steps 
                (rca_id, step_number, question, answer, is_key_cause, created_at)
                VALUES (:rca_id, :step_number, :question, :answer, :is_key_cause, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rca_id' => $rca_id,
            ':step_number' => $nextStepNumber,
            ':question' => $question,
            ':answer' => $input['answer'] ?? '',
            ':is_key_cause' => isset($input['is_key_cause']) ? (int)$input['is_key_cause'] : 0
        ]);
        
        $newStepId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'step_id' => $newStepId,
            'step_number' => $nextStepNumber,
            'message' => 'Step created successfully'
        ]);
    } elseif ($method === 'PUT' && $step_id) {
        // Update step
        $updateFields = [];
        $params = [':step_id' => $step_id];
        
        if (isset($input['why_question']) || isset($input['question'])) {
            $updateFields[] = "question = :question";
            $params[':question'] = $input['why_question'] ?? $input['question'];
        }
        if (isset($input['answer'])) {
            $updateFields[] = "answer = :answer";
            $params[':answer'] = $input['answer'];
        }
        if (isset($input['is_key_cause'])) {
            $updateFields[] = "is_key_cause = :is_key_cause";
            $params[':is_key_cause'] = (int)$input['is_key_cause'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit;
        }
        
        $sql = "UPDATE rca_5whys_steps SET " . implode(', ', $updateFields) . " WHERE step_id = :step_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Step updated successfully'
        ]);
    } elseif ($method === 'DELETE' && $step_id) {
        // Delete step
        $sql = "DELETE FROM rca_5whys_steps WHERE step_id = :step_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':step_id' => $step_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Step deleted successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

?>


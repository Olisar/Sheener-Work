<?php
/* File: sheener/api/rca/fishbone_causes.php */

/**
 * Fishbone Causes API
 * Handles CRUD operations for Fishbone causes
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
    $cause_id = $_GET['cause_id'] ?? $input['cause_id'] ?? null;
    $category_id = $_GET['category_id'] ?? $input['category_id'] ?? null;
    
    if ($method === 'POST') {
        // Create new cause
        if (!$category_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'category_id is required']);
            exit;
        }
        
        $description = $input['description'] ?? '';
        $evidence_reference = $input['evidence_reference'] ?? null;
        $is_primary_cause = isset($input['is_primary_cause']) ? (int)$input['is_primary_cause'] : 0;
        
        $sql = "INSERT INTO rca_fishbone_causes 
                (category_id, description, evidence_reference, is_primary_cause, created_at)
                VALUES (:category_id, :description, :evidence_reference, :is_primary_cause, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':category_id' => $category_id,
            ':description' => $description,
            ':evidence_reference' => $evidence_reference,
            ':is_primary_cause' => $is_primary_cause
        ]);
        
        $newCauseId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'cause_id' => $newCauseId,
            'message' => 'Cause created successfully'
        ]);
    } elseif ($method === 'PUT' && $cause_id) {
        // Update cause
        $updateFields = [];
        $params = [':cause_id' => $cause_id];
        
        if (isset($input['description'])) {
            $updateFields[] = "description = :description";
            $params[':description'] = $input['description'];
        }
        if (isset($input['evidence_reference'])) {
            $updateFields[] = "evidence_reference = :evidence_reference";
            $params[':evidence_reference'] = $input['evidence_reference'];
        }
        if (isset($input['is_primary_cause'])) {
            $updateFields[] = "is_primary_cause = :is_primary_cause";
            $params[':is_primary_cause'] = (int)$input['is_primary_cause'];
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit;
        }
        
        $sql = "UPDATE rca_fishbone_causes SET " . implode(', ', $updateFields) . " WHERE cause_id = :cause_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cause updated successfully'
        ]);
    } elseif ($method === 'DELETE' && $cause_id) {
        // Delete cause
        $sql = "DELETE FROM rca_fishbone_causes WHERE cause_id = :cause_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cause_id' => $cause_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cause deleted successfully'
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


<?php
/* File: sheener/php/assign_training.php */

// Assign training to a person for a document version
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        @ob_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']
        ]);
        exit;
    }
});

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
    
    if (!$data || !isset($data['personId']) || !isset($data['docVersionId'])) {
        sendJsonResponse(['success' => false, 'error' => 'Missing required fields: personId and docVersionId'], 400);
    }
    
    $personId = intval($data['personId']);
    $docVersionId = intval($data['docVersionId']);
    $assignedBy = isset($data['assignedBy']) ? trim($data['assignedBy']) : 'admin';
    
    if ($personId <= 0 || $docVersionId <= 0) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid personId or docVersionId'], 400);
    }
    
    // Verify person exists
    $stmt = $pdo->prepare('SELECT people_id FROM people WHERE people_id = ? AND IsActive = 1');
    $stmt->execute([$personId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(['success' => false, 'error' => 'Person not found or inactive'], 404);
    }
    
    // Verify document version exists
    $stmt = $pdo->prepare('SELECT VersionID FROM documentversions WHERE VersionID = ?');
    $stmt->execute([$docVersionId]);
    if (!$stmt->fetch()) {
        sendJsonResponse(['success' => false, 'error' => 'Document version not found'], 404);
    }
    
    // Get assigned_by as people_id (if string 'admin', find an admin user, otherwise use the ID)
    $assignedById = null;
    if (is_numeric($assignedBy)) {
        $assignedById = intval($assignedBy);
    } else {
        // Try to find an active admin user, or use personId as fallback
        $stmt = $pdo->prepare('SELECT people_id FROM people WHERE IsActive = 1 LIMIT 1');
        $stmt->execute();
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
        $assignedById = $adminUser ? intval($adminUser['people_id']) : $personId;
    }
    
    // Calculate due date (default to 30 days from now)
    $dueDate = isset($data['dueDate']) ? $data['dueDate'] : date('Y-m-d', strtotime('+30 days'));
    
    // Insert assignment - match actual table schema:
    // assigned_by is INT(11) referencing people_id
    // assigned_date (not assigned_at)
    // status is ENUM('pending','in-progress','completed','overdue') default 'pending'
    // due_date is required (NOT NULL)
    $stmt = $pdo->prepare('
        INSERT INTO training_assignments (person_id, doc_version_id, assigned_by, due_date, status)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$personId, $docVersionId, $assignedById, $dueDate, 'pending']);
    
    $assignmentId = $pdo->lastInsertId();
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Training assigned successfully',
        'assignmentId' => $assignmentId
    ]);
    
} catch (PDOException $e) {
    error_log('assign_training.php PDOException: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ], 500);
} catch (Exception $e) {
    error_log('assign_training.php Exception: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
} catch (Error $e) {
    error_log('assign_training.php Error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}

<?php
/* File: sheener/php/update_training_assignment.php */

// Update training assignment - change person or document
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
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()], 400);
}

$assignmentId = isset($data['assignmentId']) ? intval($data['assignmentId']) : null;
$personId = isset($data['personId']) ? intval($data['personId']) : null;
$docVersionId = isset($data['docVersionId']) ? intval($data['docVersionId']) : null;

if (!$assignmentId) {
    sendJsonResponse(['success' => false, 'error' => 'Assignment ID is required'], 400);
}

if (!$personId || !$docVersionId) {
    sendJsonResponse(['success' => false, 'error' => 'Both person ID and document version ID are required'], 400);
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verify assignment exists
    $checkStmt = $pdo->prepare('SELECT id FROM training_assignments WHERE id = ?');
    $checkStmt->execute([$assignmentId]);
    if ($checkStmt->rowCount() === 0) {
        sendJsonResponse(['success' => false, 'error' => 'Assignment not found'], 404);
    }
    
    // Verify person exists
    $checkPerson = $pdo->prepare('SELECT people_id FROM people WHERE people_id = ? AND IsActive = 1');
    $checkPerson->execute([$personId]);
    if ($checkPerson->rowCount() === 0) {
        sendJsonResponse(['success' => false, 'error' => 'Person not found or inactive'], 400);
    }
    
    // Verify document version exists
    $checkDoc = $pdo->prepare('SELECT VersionID FROM documentversions WHERE VersionID = ?');
    $checkDoc->execute([$docVersionId]);
    if ($checkDoc->rowCount() === 0) {
        sendJsonResponse(['success' => false, 'error' => 'Document version not found'], 400);
    }
    
    // Update the assignment
    $stmt = $pdo->prepare('
        UPDATE training_assignments 
        SET person_id = ?, doc_version_id = ?
        WHERE id = ?
    ');
    $stmt->execute([$personId, $docVersionId, $assignmentId]);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Training assignment updated successfully',
        'assignmentId' => $assignmentId
    ]);
    
} catch (PDOException $e) {
    error_log('update_training_assignment.php PDOException: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode()
    ], 500);
} catch (Exception $e) {
    error_log('update_training_assignment.php Exception: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
} catch (Error $e) {
    error_log('update_training_assignment.php Error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}

?>


<?php
/* File: sheener/php/create_quiz.php */

// Create a new quiz for a document version
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();
require_once 'database.php';

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST method required']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    
    // Get current user ID from session
    $createdBy = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    
    if (!$createdBy || $createdBy <= 0) {
        // Try to get a default admin user if session is not available
        $stmt = $pdo->prepare('SELECT people_id FROM people WHERE people_id = 1 LIMIT 1');
        $stmt->execute();
        $defaultUser = $stmt->fetchColumn();
        if ($defaultUser) {
            $createdBy = intval($defaultUser);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not authenticated. Please log in.']);
            exit;
        }
    }
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['doc_version_id'])) {
        echo json_encode(['success' => false, 'error' => 'doc_version_id is required']);
        exit;
    }
    
    $docVersionId = intval($data['doc_version_id']);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $passingScore = isset($data['passing_score']) ? floatval($data['passing_score']) : 70;
    $active = isset($data['active']) ? intval($data['active']) : 1;
    
    if ($docVersionId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid doc_version_id']);
        exit;
    }
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Quiz title is required']);
        exit;
    }
    
    // Verify document version exists
    $stmt = $pdo->prepare('SELECT VersionID FROM documentversions WHERE VersionID = ?');
    $stmt->execute([$docVersionId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Document version not found']);
        exit;
    }
    
    // Check if quiz already exists for this version
    $stmt = $pdo->prepare('SELECT id FROM quizzes WHERE doc_version_id = ? AND active = 1');
    $stmt->execute([$docVersionId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A quiz already exists for this document version']);
        exit;
    }
    
    // Insert new quiz with created_by field
    $stmt = $pdo->prepare('
        INSERT INTO quizzes (doc_version_id, title, description, passing_score, active, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([$docVersionId, $title, $description, $passingScore, $active, $createdBy]);
    $quizId = (int)$pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'quiz_id' => $quizId,
        'message' => 'Quiz created successfully'
    ]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    error_log("Create quiz PDO error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("Create quiz error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    error_log("Create quiz fatal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
    exit;
}


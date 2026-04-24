<?php
/* File: sheener/php/delete_attachment.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
}

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['attachment_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing attachment_id']);
    exit;
}

$attachment_id = intval($_POST['attachment_id']);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get file path before deletion
    $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE attachment_id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM attachments WHERE attachment_id = ?");
    $stmt->execute([$attachment_id]);
    
    // Delete physical file from existing uploads folder
    if ($attachment && $attachment['file_path']) {
        // Handle both relative and absolute paths
        $filePath = $attachment['file_path'];
        if (strpos($filePath, 'php/uploads/') === 0) {
            // Relative path (php/uploads/filename), convert to absolute path
            $filePath = __DIR__ . '/' . str_replace('php/uploads/', 'uploads/', $filePath);
        } elseif (!file_exists($filePath)) {
            // Try as relative path if absolute doesn't exist
            $filePath = __DIR__ . '/uploads/' . basename($attachment['file_path']);
        }
        
        // Delete file if it exists
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

<?php
/* File: sheener/php/get_task_attachments.php */

header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

require_once 'database.php';

$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
if (!$task_id) {
    die(json_encode(['success' => false, 'error' => 'Missing task_id']));
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare(
        "SELECT attachment_id, file_name AS filename, file_type, file_size, file_path, description, uploaded_at, u.FirstName, u.LastName
         FROM   attachments a
         LEFT JOIN people u ON a.uploaded_by = u.people_id
         WHERE  a.task_id = ?
         ORDER BY uploaded_at DESC"
    );
    $stmt->execute([$task_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($attachments as &$attachment) {
        $attachment['uploaded_by_name'] = trim(($attachment['FirstName'] ?? '') . ' ' . ($attachment['LastName'] ?? '')) ?: 'Unknown';
    }
    
    echo json_encode(['success' => true, 'attachments' => $attachments]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>

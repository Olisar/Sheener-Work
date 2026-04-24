<?php
/* File: sheener/php/delete_version_attachment.php */

// php/delete_version_attachment.php
header('Content-Type: application/json');

require_once __DIR__ . '/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['attachment_id'])) {
        throw new Exception('attachment_id is required');
    }
    
    $attachmentId = (int)$input['attachment_id'];
    
    // Get file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE attachment_id = ?");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        throw new Exception('Attachment not found');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM attachments WHERE attachment_id = ?");
    $stmt->execute([$attachmentId]);
    
    // Delete file from disk
    if (!empty($attachment['file_path'])) {
        $filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $attachment['file_path']);
        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Attachment deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


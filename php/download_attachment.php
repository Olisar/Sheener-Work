<?php
/* File: sheener/php/download_attachment.php */

// php/download_attachment.php
session_start();
require_once 'database.php';

if (!isset($_GET['attachment_id'])) {
    http_response_code(400);
    die('Missing attachment_id');
}

$attachment_id = intval($_GET['attachment_id']);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT file_name, file_type, file_path FROM attachments WHERE attachment_id = ?");
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment || !$attachment['file_path'] || !file_exists($attachment['file_path'])) {
        http_response_code(404);
        die('File not found');
    }
    
    header('Content-Type: ' . $attachment['file_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
    header('Content-Length: ' . filesize($attachment['file_path']));
    readfile($attachment['file_path']);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}
?>

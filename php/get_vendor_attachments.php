<?php
/* File: sheener/php/get_vendor_attachments.php */

/*  Sheener / php / get_vendor_attachments.php  */
header('Content-Type: application/json');
session_start();
require_once 'database.php';

$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
if (!$vendor_id) {
    die(json_encode(['success' => false, 'error' => 'Missing vendor_id']));
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare(
        "SELECT attachment_id, file_name AS filename, file_type, file_size, file_path, description, uploaded_at, u.FirstName, u.LastName
         FROM   attachments a
         LEFT JOIN people u ON a.uploaded_by = u.people_id
         WHERE  a.vendor_id = ?
         ORDER BY uploaded_at DESC"
    );
    $stmt->execute([$vendor_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add full path for convenience
    foreach ($attachments as &$attachment) {
        $attachment['uploaded_by_name'] = trim(($attachment['FirstName'] ?? '') . ' ' . ($attachment['LastName'] ?? '')) ?: 'Unknown';
    }
    
    echo json_encode(['success' => true, 'attachments' => $attachments]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

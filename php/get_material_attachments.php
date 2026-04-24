<?php
/* File: sheener/php/get_material_attachments.php */

/*  Sheener / php / get_material_attachments.php  */
header('Content-Type: application/json');
session_start();
require_once 'database.php';

$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
if (!$material_id) {
    die(json_encode(['success' => false, 'error' => 'Missing material_id']));
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Ensure column exists for this material lookup
    try {
        $pdo->query("SELECT material_id FROM attachments LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE attachments ADD COLUMN material_id INT NULL AFTER task_id");
    }

    $stmt = $pdo->prepare(
        "SELECT attachment_id, file_name AS filename, file_type, file_size, file_path, description, uploaded_at, u.FirstName, u.LastName
         FROM   attachments a
         LEFT JOIN people u ON a.uploaded_by = u.people_id
         WHERE  a.material_id = ?
         ORDER BY uploaded_at DESC"
    );
    $stmt->execute([$material_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($attachments as &$attachment) {
        $attachment['uploaded_by_name'] = trim(($attachment['FirstName'] ?? '') . ' ' . ($attachment['LastName'] ?? '')) ?: 'Unknown';
    }
    
    echo json_encode(['success' => true, 'attachments' => $attachments]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

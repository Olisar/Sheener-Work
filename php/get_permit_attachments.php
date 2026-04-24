<?php
/* File: sheener/php/get_permit_attachments.php */

/*  Sheener / php / get_permit_attachments.php  */
header('Content-Type: application/json');
session_start();
require_once 'database.php';

$permit_id = isset($_GET['permit_id']) ? intval($_GET['permit_id']) : 0;
if (!$permit_id)  die(json_encode(['success' => false, 'error' => 'Missing permit_id']));

try {
    $db  = new Database();
    $pdo = $db->getConnection();
    $stmt= $pdo->prepare(
        "SELECT attachment_id, file_name AS filename, file_type, file_size, file_path, description
         FROM   attachments
         WHERE  permit_id = ?"
    );
    $stmt->execute([$permit_id]);
    echo json_encode(['success' => true, 'attachments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

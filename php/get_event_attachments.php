<?php
/* File: sheener/php/get_event_attachments.php */

/*  Sheener / php / get_event_attachments.php  */
header('Content-Type: application/json');
session_start();
require_once 'database.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id)  die(json_encode(['success' => false, 'error' => 'Missing event_id']));

try {
    $db  = new Database();
    $pdo = $db->getConnection();
    $stmt= $pdo->prepare(
        "SELECT attachment_id, file_name AS filename, file_type, file_size, file_path, description
         FROM   attachments
         WHERE  event_id = ?"
    );
    $stmt->execute([$event_id]);
    echo json_encode(['success' => true, 'attachments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>




<?php
/* File: sheener/php/get_audit_logs.php */

// file name Sheener/php/get_audit_logs.php
require_once 'database.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_GET['permit_id'])) {
    die(json_encode(['success' => false, 'error' => 'Missing permit ID']));
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT l.action, u.username AS user, l.timestamp 
        FROM permit_audit_logs l
        JOIN users u ON l.user_id = u.user_id
        WHERE l.permit_id = ?
        ORDER BY l.timestamp DESC
    ");
    $stmt->execute([$_GET['permit_id']]);
    
    echo json_encode(['success' => true, 'logs' => $stmt->fetchAll()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

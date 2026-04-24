<?php
/* File: sheener/php/delete_permit.php */

// php/delete_permit.php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// CSRF token check
if (empty($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Missing CSRF token.']);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token not found in session. Please refresh.']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token mismatch. Please refresh.']);
    exit;
}

if (!isset($_POST['permit_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing permit ID.']);
    exit;
}

$permit_id = intval($_POST['permit_id']);

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $pdo->beginTransaction();

    // Delete sub-permit links (where this permit is the main parent)
// Delete steps first
$pdo->prepare("DELETE FROM permit_steps WHERE permit_id = ?")->execute([$permit_id]);

// Delete attachments
$pdo->prepare("DELETE FROM attachments WHERE permit_id = ?")->execute([$permit_id]);

// Delete sub-permits relationships (child links to this permit as parent)
$pdo->prepare("DELETE FROM sub_permits WHERE main_permit_id = ?")->execute([$permit_id]);

// Now delete the permit
$pdo->prepare("DELETE FROM permits WHERE permit_id = ?")->execute([$permit_id]);

// LOG TO AUDIT LOG
$db->logAudit($pdo, 'DELETE', 'permits', $permit_id, "Deleted permit ID: {$permit_id}");


    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Deletion failed: ' . $e->getMessage()]);
}
?>

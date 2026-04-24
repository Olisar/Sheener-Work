<?php
/* File: sheener/php/approve_hira.php */

// php/approve_hira.php
declare(strict_types=1);

require __DIR__ . '/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$hira_id     = isset($input['hira_id']) ? (int)$input['hira_id'] : null;
$approved_by = isset($input['approved_by']) ? (int)$input['approved_by'] : null; // people.people_id
$status_id   = isset($input['status_id']) ? (int)$input['status_id'] : 1;        // 1 = approved

if (!$hira_id || !$approved_by) {
  http_response_code(400);
  echo json_encode(['error' => 'hira_id and approved_by are required']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("CALL sp_approve_hira(:hira_id,:people_id,:status_id)");
  $stmt->execute([
    ':hira_id'   => $hira_id,
    ':people_id' => $approved_by,
    ':status_id' => $status_id
  ]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['ok' => true, 'result' => $row]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

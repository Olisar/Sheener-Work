<?php
/* File: sheener/php/finalize_hira.php */

// php/finalize_hira.php
declare(strict_types=1);

require __DIR__ . '/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$hira_id = isset($input['hira_id']) ? (int)$input['hira_id'] : null;

if (!$hira_id) {
  http_response_code(400);
  echo json_encode(['error' => 'hira_id is required']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("CALL sp_finalize_hira(:h)");
  $stmt->execute([':h' => $hira_id]);
  echo json_encode(['ok' => true, 'hira_id' => $hira_id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

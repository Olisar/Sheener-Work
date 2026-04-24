<?php
/* File: sheener/php/create_hira.php */

// php/create_hira.php
declare(strict_types=1);

require __DIR__ . '/database.php'; // uses your existing connection helper

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$scope_type  = $input['scope_type']  ?? null;  // 'Project' | 'Task' | 'Permit' | 'Change' | 'Batch' | 'Generic'
$scope_id    = isset($input['scope_id']) ? (int)$input['scope_id'] : null;
$stage_name  = $input['stage_name']  ?? null;  // e.g. 'Installation & Commissioning'
$assessor_id = isset($input['assessor_id']) ? (int)$input['assessor_id'] : null;

if (!$scope_type || !$scope_id || !$stage_name) {
  http_response_code(400);
  echo json_encode(['error' => 'scope_type, scope_id, stage_name are required']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("CALL sp_create_hira_for_stage(:scope_type,:scope_id,:stage_name,:assessor_id)");
  $stmt->execute([
    ':scope_type'  => $scope_type,
    ':scope_id'    => $scope_id,
    ':stage_name'  => $stage_name,
    ':assessor_id' => $assessor_id
  ]);

  // sp returns SELECT v_hira AS hira_id
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $hira_id = $row['hira_id'] ?? null;

  echo json_encode(['ok' => true, 'hira_id' => (int)$hira_id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

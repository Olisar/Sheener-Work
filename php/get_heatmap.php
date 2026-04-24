<?php
/* File: sheener/php/get_heatmap.php */

// php/get_heatmap.php
declare(strict_types=1);

require __DIR__ . '/database.php';

header('Content-Type: application/json');

$hira_id    = isset($_GET['hira_id']) ? (int)$_GET['hira_id'] : null;
$scope_type = $_GET['scope_type'] ?? null;
$scope_id   = isset($_GET['scope_id']) ? (int)$_GET['scope_id'] : null;

try {
  $pdo = db();

  if ($hira_id) {
    $stmt = $pdo->prepare("SELECT * FROM v_hira_stage_heatmap WHERE hira_id=:h");
    $stmt->execute([':h' => $hira_id]);
    echo json_encode(['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  if ($scope_type && $scope_id) {
    $stmt = $pdo->prepare("SELECT * FROM v_hira_stage_heatmap WHERE scope_type=:t AND scope_id=:i");
    $stmt->execute([':t' => $scope_type, ':i' => $scope_id]);
    echo json_encode(['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
  }

  // default: last 100
  $stmt = $pdo->query("SELECT * FROM v_hira_stage_heatmap ORDER BY hira_id DESC LIMIT 100");
  echo json_encode(['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

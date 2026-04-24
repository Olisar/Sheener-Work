<?php
/* File: sheener/php/get_network.php */

// php/get_network.php
declare(strict_types=1);

require __DIR__ . '/database.php';

header('Content-Type: application/json');

$hira_id = isset($_GET['hira_id']) ? (int)$_GET['hira_id'] : null;
if (!$hira_id) {
  http_response_code(400);
  echo json_encode(['error' => 'hira_id is required']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM v_component_risk_network WHERE hira_id = :h");
  $stmt->execute([':h' => $hira_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $nodes = [];
  $index = [];
  $edges = [];

  foreach ($rows as $r) {
    // Handle null component ids by creating stable fallbacks
    $sKey = $r['source_component_id'] ?: ('S:hz:'.$r['hazard_id']);
    $tKey = $r['target_component_id'] ?: ('T:risk:'.$r['risk_id']);

    if (!isset($index[$sKey])) {
      $index[$sKey] = count($nodes);
      $nodes[] = [
        'id'   => $sKey,
        'name' => $r['source_name'] ?: 'Unknown Source',
        'type' => 'component'
      ];
    }
    if (!isset($index[$tKey])) {
      $index[$tKey] = count($nodes);
      $nodes[] = [
        'id'   => $tKey,
        'name' => $r['target_name'] ?: 'Unknown Target',
        'type' => 'component'
      ];
    }

    $edges[] = [
      'source' => $index[$sKey],
      'target' => $index[$tKey],
      'hazard_id'   => (int)$r['hazard_id'],
      'risk_id'     => (int)$r['risk_id'],
      'risk_before' => (float)$r['risk_before'],
      'risk_after'  => isset($r['risk_after']) ? (float)$r['risk_after'] : null,
      'L' => (int)$r['likelihood_before'],
      'S' => (int)$r['severity_before'],
      'E' => isset($r['exposure']) ? (int)$r['exposure'] : null,
      'D' => isset($r['detectability']) ? (int)$r['detectability'] : null
    ];
  }

  echo json_encode([
    'hira_id'    => $hira_id,
    'stage_name' => $rows[0]['stage_name'] ?? null,
    'nodes'      => $nodes,
    'edges'      => $edges,
    'raw'        => $rows
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}

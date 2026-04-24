<?php
/* File: sheener/php/create_hira_records.php */

/**
 * Create HIRA Records for Hazard Links
 * 
 * This script creates HIRA records that are needed for hazard links.
 * Run this before importing hazard links if the HIRA records don't exist.
 */

require __DIR__ . '/database.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // HIRA records to create
    // Format: [hira_id, scope_type, scope_id, stage_id, assessor_id, status, notes]
    $hira_records = [
        [201, 'Task', 101, 1, NULL, 'Draft', 'HIRA for Task 101 - API Weighing'],
        [202, 'Task', 102, 2, NULL, 'Draft', 'HIRA for Task 102 - Powder Blending'],
        [203, 'Task', 103, 3, NULL, 'Draft', 'HIRA for Task 103 - MDI Filling']
    ];
    
    $created = 0;
    $skipped = 0;
    $errors = [];
    
    // Get valid stage_ids
    $stage_ids = array_unique(array_column($hira_records, 3));
    $placeholders = str_repeat('?,', count($stage_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT stage_id FROM lifecycle_stages WHERE stage_id IN ($placeholders)");
    $stmt->execute($stage_ids);
    $valid_stage_ids = array_column($stmt->fetchAll(), 'stage_id');
    
    // Check if tasks exist
    $task_ids = array_unique(array_column($hira_records, 2));
    $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT task_id FROM tasks WHERE task_id IN ($placeholders)");
    $stmt->execute($task_ids);
    $valid_task_ids = array_column($stmt->fetchAll(), 'task_id');
    
    $sql = "INSERT INTO hira_register 
            (hira_id, scope_type, scope_id, stage_id, assessor_id, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
              scope_type = VALUES(scope_type),
              scope_id = VALUES(scope_id),
              stage_id = VALUES(stage_id),
              assessor_id = VALUES(assessor_id),
              status = VALUES(status),
              notes = VALUES(notes)";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($hira_records as $hira) {
        $hira_id = $hira[0];
        $scope_type = $hira[1];
        $scope_id = $hira[2];
        $stage_id = $hira[3];
        $assessor_id = $hira[4];
        $status = $hira[5];
        $notes = $hira[6];
        
        // Validate stage_id
        if (!in_array($stage_id, $valid_stage_ids)) {
            $errors[] = "HIRA ID $hira_id: Stage ID $stage_id does not exist. Skipping.";
            $skipped++;
            continue;
        }
        
        // Warn if task_id doesn't exist but still create the HIRA record
        // (HIRA records can exist even if the referenced task doesn't exist yet)
        if ($scope_type === 'Task' && !in_array($scope_id, $valid_task_ids)) {
            $errors[] = "HIRA ID $hira_id: Task ID $scope_id does not exist, but creating HIRA record anyway.";
            // Continue to create the record - don't skip
        }
        
        try {
            $stmt->execute([$hira_id, $scope_type, $scope_id, $stage_id, $assessor_id, $status, $notes]);
            $created++;
        } catch (PDOException $e) {
            $errors[] = "HIRA ID $hira_id: " . $e->getMessage();
            $skipped++;
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'HIRA records created successfully',
        'summary' => [
            'created' => $created,
            'skipped' => $skipped
        ],
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


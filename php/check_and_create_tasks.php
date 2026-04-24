<?php
/* File: sheener/php/check_and_create_tasks.php */

/**
 * Check and Create Missing Tasks
 * 
 * This script checks if tasks 101-110 exist, and creates them if they don't.
 * Run this before importing hazards.
 */

require __DIR__ . '/database.php';

header('Content-Type: application/json');
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Tasks that need to exist for the hazards import
    $required_tasks = [
        101 => 'API Weighing',
        102 => 'Powder Blending',
        103 => 'MDI Filling',
        104 => 'DPI Filling',
        105 => 'Packaging',
        106 => 'CIP Cleaning',
        107 => 'Maintenance',
        108 => 'Labelling',
        109 => 'QC Sampling',
        110 => 'Storage'
    ];
    
    $created = 0;
    $existing = 0;
    $errors = [];
    
    // Check which tasks exist
    $task_ids = array_keys($required_tasks);
    $placeholders = str_repeat('?,', count($task_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT task_id, task_name FROM tasks WHERE task_id IN ($placeholders)");
    $stmt->execute($task_ids);
    $existing_tasks = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_tasks[$row['task_id']] = $row['task_name'];
    }
    
    // Get a default department_id (use first available)
    $stmt = $pdo->query("SELECT department_id FROM departments LIMIT 1");
    $default_dept = $stmt->fetch(PDO::FETCH_COLUMN);
    if (!$default_dept) {
        throw new Exception("No departments found. Please create at least one department first.");
    }
    
    // Check current AUTO_INCREMENT value and set it if needed
    // This allows us to insert specific task_ids
    try {
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'tasks'");
        $table_status = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_auto_increment = $table_status['Auto_increment'] ?? 1;
        $max_required_id = max($task_ids);
        
        // If we need IDs higher than current auto_increment, set it
        if ($max_required_id >= $current_auto_increment) {
            $pdo->exec("ALTER TABLE tasks AUTO_INCREMENT = " . intval($max_required_id + 1));
        }
    } catch (PDOException $e) {
        // If we can't modify AUTO_INCREMENT, that's okay - we'll try to insert anyway
        // MySQL will use the next available ID if the specified one is taken
        $errors[] = "Warning: Could not modify AUTO_INCREMENT: " . $e->getMessage();
    }
    
    // Prepare insert statement
    // Note: task_type is required, status should be 'Not Started' not 'Pending'
    $insert_sql = "INSERT INTO tasks (task_id, task_name, task_description, start_date, priority, status, task_type, department_id) 
                   VALUES (?, ?, ?, CURDATE(), 'Medium', 'Not Started', 'Operational Task', ?) 
                   ON DUPLICATE KEY UPDATE 
                     task_name = VALUES(task_name),
                     task_description = VALUES(task_description)";
    
    $insert_stmt = $pdo->prepare($insert_sql);
    
    foreach ($required_tasks as $task_id => $task_name) {
        if (isset($existing_tasks[$task_id])) {
            $existing++;
            continue;
        }
        
        try {
            $description = "Task for $task_name operations";
            $insert_stmt->execute([$task_id, $task_name, $description, $default_dept]);
            $created++;
        } catch (PDOException $e) {
            $errors[] = "Task ID $task_id: " . $e->getMessage();
        }
    }
    
    // Commit transaction
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Task check completed',
        'summary' => [
            'created' => $created,
            'existing' => $existing,
            'total_required' => count($required_tasks)
        ],
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors
        }
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    if (isset($pdo)) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (PDOException $rollbackError) {
            // Ignore rollback errors
        }
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}


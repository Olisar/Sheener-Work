<?php
// file: sheener/php/taskfetch.php

require_once 'database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // DEBUG: Check available tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    error_log('Available tables: ' . print_r($tables, true));

    // Build query dynamically based on available schema
    $columns = ['t.task_id', 't.department_id', 'd.DepartmentName', 
                't.assigned_to', 't.task_name', 
                't.start_date', 't.finish_date', 't.due_date', 
                't.priority', 't.status'];

    // Check if tasks has batch_id column
    $taskColumns = $pdo->query("SHOW COLUMNS FROM tasks")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('batch_id', $taskColumns)) {
        // Check if batches table exists and has batch_name
        if (in_array('batches', $tables)) {
            $batchColumns = $pdo->query("SHOW COLUMNS FROM batches")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('batch_name', $batchColumns)) {
                $columns[] = 'b.batch_name';
            } else {
                error_log('WARNING: batches.batch_name column does not exist');
            }
        } else {
            error_log('WARNING: batches table does not exist');
        }
    }

    $columnString = implode(', ', $columns);
    
    $query = "
        SELECT $columnString
        FROM tasks t
        LEFT JOIN departments d ON t.department_id = d.department_id
    ";

    // Add batches join only if we included batch_name
    if (in_array('b.batch_name', $columns)) {
        $query .= " LEFT JOIN batches b ON t.batch_id = b.batch_id";
    }

    error_log('Executing query: ' . $query);
    
    $stmt = $pdo->query($query);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates
    foreach ($tasks as &$task) {
        $task['start_date'] = $task['start_date'] ? date('Y-m-d', strtotime($task['start_date'])) : null;
        $task['finish_date'] = $task['finish_date'] ? date('Y-m-d', strtotime($task['finish_date'])) : null;
        $task['due_date'] = $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : null;
    }

    echo json_encode($tasks);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'query' => $query ?? null
    ]);
    error_log('Database error: ' . $e->getMessage());
}
exit;
?>

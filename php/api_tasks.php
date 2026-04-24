<?php
/* File: sheener/php/api_tasks.php */

/**
 * Tasks API
 * Handles task operations
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $action = $_GET['action'] ?? 'list';
    $id = $_GET['id'] ?? null;
    $processId = $_GET['process_id'] ?? null;
    
    switch ($action) {
        case 'list':
            $response = listTasks($pdo, $processId);
            break;
            
        case 'detail':
            if (!$id) {
                throw new Exception('ID required');
            }
            $response = getTaskDetail($pdo, $id);
            break;
            
        case 'create':
            $response = createTask($pdo);
            break;
            
        case 'update':
            if (!$id) {
                throw new Exception('ID required');
            }
            $response = updateTask($pdo, $id);
            break;
            
        case 'delete':
            if (!$id) {
                throw new Exception('ID required');
            }
            $response = deleteTask($pdo, $id);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function listTasks($pdo, $processId = null) {
    $params = [];
    
    if ($processId) {
        // Link via process_map_task junction table
        $sql = "
            SELECT 
                t.task_id,
                t.task_name,
                t.task_description,
                t.task_type,
                t.priority,
                t.status,
                t.due_date,
                t.start_date,
                t.finish_date,
                t.assigned_to,
                CONCAT(p.FirstName, ' ', p.LastName) as assigned_to_name,
                t.created_date,
                pmt.process_map_id,
                pmt.linked_date
            FROM tasks t
            INNER JOIN process_map_task pmt ON t.task_id = pmt.task_id
            LEFT JOIN people p ON t.assigned_to = p.people_id
            WHERE pmt.process_map_id = :process_id
            ORDER BY t.due_date ASC, t.priority DESC, t.created_date DESC
        ";
        $params[':process_id'] = $processId;
    } else {
        // Get all tasks
        $sql = "
            SELECT 
                t.task_id,
                t.task_name,
                t.task_description,
                t.task_type,
                t.priority,
                t.status,
                t.due_date,
                t.start_date,
                t.finish_date,
                t.assigned_to,
                CONCAT(p.FirstName, ' ', p.LastName) as assigned_to_name,
                t.created_date
            FROM tasks t
            LEFT JOIN people p ON t.assigned_to = p.people_id
            ORDER BY t.due_date ASC, t.priority DESC, t.created_date DESC
        ";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $tasks
        ];
    } catch (PDOException $e) {
        // If process_map_task table doesn't exist, fall back to all tasks
        if ($processId && strpos($e->getMessage(), "process_map_task") !== false) {
            $sql = "
                SELECT 
                    t.task_id,
                    t.task_name,
                    t.task_description,
                    t.task_type,
                    t.priority,
                    t.status,
                    t.due_date,
                    t.start_date,
                    t.finish_date,
                    t.assigned_to,
                    CONCAT(p.FirstName, ' ', p.LastName) as assigned_to_name,
                    t.created_date
                FROM tasks t
                LEFT JOIN people p ON t.assigned_to = p.people_id
                ORDER BY t.due_date ASC, t.priority DESC, t.created_date DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $tasks,
                'note' => 'Process filtering not available - process_map_task table not found'
            ];
        }
        throw $e;
    }
}

function getTaskDetail($pdo, $id) {
    try {
        // Start with basic task query
        $sql = "
            SELECT 
                t.*
            FROM tasks t
            WHERE t.task_id = :id
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            throw new Exception('Task not found');
        }
        
        // Try to get assigned person name
        if (!empty($task['assigned_to'])) {
            try {
                $personStmt = $pdo->prepare("
                    SELECT 
                        CONCAT(COALESCE(FirstName, ''), ' ', COALESCE(LastName, '')) as full_name
                    FROM people
                    WHERE people_id = :people_id
                ");
                $personStmt->execute([':people_id' => $task['assigned_to']]);
                $person = $personStmt->fetch(PDO::FETCH_ASSOC);
                $task['assigned_to_name'] = $person ? trim($person['full_name']) : null;
            } catch (PDOException $e) {
                $task['assigned_to_name'] = null;
            }
        } else {
            $task['assigned_to_name'] = null;
        }
        
        // Try to get department name
        if (!empty($task['department_id'])) {
            try {
                $deptStmt = $pdo->prepare("
                    SELECT department_name
                    FROM departments
                    WHERE department_id = :dept_id
                ");
                $deptStmt->execute([':dept_id' => $task['department_id']]);
                $dept = $deptStmt->fetch(PDO::FETCH_ASSOC);
                $task['department_name'] = $dept ? $dept['department_name'] : null;
            } catch (PDOException $e) {
                $task['department_name'] = null;
            }
        } else {
            $task['department_name'] = null;
        }
        
        // Get linked processes if process_map_task table exists
        try {
            $processStmt = $pdo->prepare("
                SELECT 
                    pm.id as process_map_id,
                    pm.text as process_name,
                    pm.type as process_type,
                    pmt.linked_date
                FROM process_map_task pmt
                INNER JOIN process_map pm ON pmt.process_map_id = pm.id
                WHERE pmt.task_id = :task_id
            ");
            $processStmt->execute([':task_id' => $id]);
            $task['linked_processes'] = $processStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // process_map_task table doesn't exist, skip
            $task['linked_processes'] = [];
        }
        
        return [
            'success' => true,
            'data' => $task
        ];
    } catch (PDOException $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    } catch (Exception $e) {
        throw $e;
    }
}

function createTask($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['task_name'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Field {$field} is required");
        }
    }
    
    // Check if workflow_step_id column exists
    $hasWorkflowStepId = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'workflow_step_id'");
        $hasWorkflowStepId = $checkStmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Column doesn't exist, continue without it
    }
    
    if ($hasWorkflowStepId) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                task_name, task_description, task_type, priority, status,
                due_date, start_date, assigned_to, department_id, workflow_step_id
            ) VALUES (
                :task_name, :task_description, :task_type, :priority, :status,
                :due_date, :start_date, :assigned_to, :department_id, :workflow_step_id
            )
        ");
        
        $stmt->execute([
            ':task_name' => $input['task_name'],
            ':task_description' => $input['task_description'] ?? null,
            ':task_type' => $input['task_type'] ?? 'Operational Task',
            ':priority' => $input['priority'] ?? 'Medium',
            ':status' => $input['status'] ?? 'Not Started',
            ':due_date' => $input['due_date'] ?? null,
            ':start_date' => $input['start_date'] ?? null,
            ':assigned_to' => $input['assigned_to'] ?? null,
            ':department_id' => $input['department_id'] ?? null,
            ':workflow_step_id' => $input['workflow_step_id'] ?? null
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                task_name, task_description, task_type, priority, status,
                due_date, start_date, assigned_to, department_id
            ) VALUES (
                :task_name, :task_description, :task_type, :priority, :status,
                :due_date, :start_date, :assigned_to, :department_id
            )
        ");
        
        $stmt->execute([
            ':task_name' => $input['task_name'],
            ':task_description' => $input['task_description'] ?? null,
            ':task_type' => $input['task_type'] ?? 'Operational Task',
            ':priority' => $input['priority'] ?? 'Medium',
            ':status' => $input['status'] ?? 'Not Started',
            ':due_date' => $input['due_date'] ?? null,
            ':start_date' => $input['start_date'] ?? null,
            ':assigned_to' => $input['assigned_to'] ?? null,
            ':department_id' => $input['department_id'] ?? null
        ]);
    }
    
    $id = $pdo->lastInsertId();
    
    // If process_map_id is provided, link the task to the process
    if (isset($input['process_map_id']) && $input['process_map_id']) {
        try {
            $linkStmt = $pdo->prepare("
                INSERT INTO process_map_task (process_map_id, task_id, linked_by)
                VALUES (:process_map_id, :task_id, :linked_by)
            ");
            $linkStmt->execute([
                ':process_map_id' => $input['process_map_id'],
                ':task_id' => $id,
                ':linked_by' => $input['linked_by'] ?? null
            ]);
        } catch (PDOException $e) {
            // process_map_task table might not exist, ignore
        }
    }
    
    return [
        'success' => true,
        'data' => ['task_id' => $id]
    ];
}

function updateTask($pdo, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $updates = [];
    $params = [':id' => $id];
    
    $allowedFields = [
        'task_name', 'task_description', 'task_type', 'priority', 'status',
        'due_date', 'start_date', 'finish_date', 'assigned_to', 'department_id'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[":{$field}"] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE task_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'message' => 'Task updated successfully'
    ];
}

function deleteTask($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE task_id = :id");
    $stmt->execute([':id' => $id]);
    
    return [
        'success' => true,
        'message' => 'Task deleted successfully'
    ];
}
?>


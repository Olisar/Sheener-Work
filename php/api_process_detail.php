<?php
/* File: sheener/php/api_process_detail.php */

/**
 * Process Detail API
 * Handles detailed process information, steps, metrics, activities, events
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
    
    $action = $_GET['action'] ?? 'detail';
    $id = $_GET['id'] ?? null;
    
    if (!$id && $action !== 'create') {
        throw new Exception('ID required');
    }
    
    switch ($action) {
        case 'detail':
            $response = getProcessDetail($pdo, $id);
            break;
            
        case 'metrics':
            $response = getProcessMetrics($pdo, $id);
            break;
            
        case 'steps':
            $response = getProcessSteps($pdo, $id);
            break;
            
        case 'activities':
            $response = getProcessActivities($pdo, $id);
            break;
            
        case 'events':
            $response = getProcessEvents($pdo, $id);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getProcessDetail($pdo, $id) {
    // Get process from process_map
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.type,
            pm.text,
            pm.parent,
            p.text as parent_text
        FROM process_map pm
        LEFT JOIN process_map p ON pm.parent = p.id
        WHERE pm.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $process = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$process) {
        throw new Exception('Process not found');
    }
    
    // Try to get additional info from process_definitions if it exists
    try {
        $stmt2 = $pdo->prepare("
            SELECT 
                version,
                status,
                effective_from,
                effective_to
            FROM process_definitions
            WHERE process_id = :id
            LIMIT 1
        ");
        $stmt2->execute([':id' => $id]);
        $def = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($def) {
            $process = array_merge($process, $def);
        }
    } catch (PDOException $e) {
        // process_definitions table might not exist
    }
    
    return [
        'success' => true,
        'data' => $process
    ];
}

function getProcessMetrics($pdo, $id) {
    // Calculate metrics
    $metrics = [
        'completion_rate' => 0,
        'compliance_score' => 100,
        'total_tasks' => 0,
        'open_issues' => 0
    ];
    
    // Get task counts
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
            FROM tasks
            WHERE workflow_step_id IN (
                SELECT step_id FROM process_steps WHERE process_id = :id
            ) OR workflow_step_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $metrics['total_tasks'] = (int)$result['total'];
            if ($metrics['total_tasks'] > 0) {
                $metrics['completion_rate'] = round(($result['completed'] / $metrics['total_tasks']) * 100);
            }
        }
    } catch (PDOException $e) {
        // Tasks table might not have workflow_step_id
    }
    
    // Get open issues/events
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM events
            WHERE process_id = :id AND status != 'Resolved'
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $metrics['open_issues'] = (int)$result['count'];
        }
    } catch (PDOException $e) {
        // Events table might not have process_id
    }
    
    return [
        'success' => true,
        'data' => $metrics
    ];
}

function getProcessSteps($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                step_id,
                step_order,
                name,
                description,
                mandatory,
                can_be_parallel
            FROM process_steps
            WHERE process_id = :id
            ORDER BY step_order, step_id
        ");
        $stmt->execute([':id' => $id]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $steps
        ];
    } catch (PDOException $e) {
        // Fallback to process_map children
        $stmt = $pdo->prepare("
            SELECT 
                id as step_id,
                type,
                text as name,
                parent
            FROM process_map
            WHERE parent = :id
            ORDER BY id
        ");
        $stmt->execute([':id' => $id]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $steps
        ];
    }
}

function getProcessActivities($pdo, $id) {
    $activities = [];
    
    // Get activities from activities table
    try {
        $stmt = $pdo->prepare("
            SELECT 
                activity_name as title,
                activity_detail as description,
                created_at,
                status
            FROM activities
            WHERE job_id IN (
                SELECT job_id FROM jobs WHERE process_id = :id
            )
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':id' => $id]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Activities might not be linked to process
    }
    
    return [
        'success' => true,
        'data' => $activities
    ];
}

function getProcessEvents($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.event_id,
                e.event_type,
                e.description,
                e.event_date,
                e.location,
                e.severity,
                e.status,
                e.created_at,
                CONCAT(p.FirstName, ' ', p.LastName) as reported_by_name
            FROM events e
            LEFT JOIN people p ON e.reported_by = p.people_id
            WHERE e.process_id = :id
            ORDER BY e.event_date DESC, e.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':id' => $id]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $events
        ];
    } catch (PDOException $e) {
        // Events might not have process_id, try alternative
        return [
            'success' => true,
            'data' => []
        ];
    }
}
?>


<?php
/* File: sheener/php/api_analytics.php */

/**
 * Analytics API
 * Handles analytics and reporting data
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $action = $_GET['action'] ?? 'kpi';
    $range = $_GET['range'] ?? 30;
    
    switch ($action) {
        case 'kpi':
            $response = getKPIs($pdo, $range);
            break;
            
        case 'overdue':
            $response = getOverdueTasks($pdo);
            break;
            
        case 'top-processes':
            $response = getTopProcesses($pdo);
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

function getKPIs($pdo, $range) {
    $dateFrom = date('Y-m-d', strtotime("-{$range} days"));
    
    // Calculate completion rate
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
        FROM tasks
        WHERE created_date >= '{$dateFrom}'
    ");
    $taskStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $completionRate = 0;
    if ($taskStats['total'] > 0) {
        $completionRate = round(($taskStats['completed'] / $taskStats['total']) * 100);
    }
    
    // Get active tasks
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM tasks
        WHERE status IN ('Not Started', 'In Progress')
    ");
    $activeTasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get open issues
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM events
            WHERE status != 'Resolved'
        ");
        $openIssues = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        $openIssues = 0;
    }
    
    // Compliance score (simplified - can be enhanced)
    $complianceScore = 100 - ($openIssues * 2);
    if ($complianceScore < 0) $complianceScore = 0;
    
    return [
        'success' => true,
        'data' => [
            'completion_rate' => $completionRate,
            'compliance_score' => $complianceScore,
            'active_tasks' => (int)$activeTasks,
            'open_issues' => (int)$openIssues
        ]
    ];
}

function getOverdueTasks($pdo) {
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            task_id,
            task_name,
            due_date,
            priority,
            status
        FROM tasks
        WHERE due_date < :today
        AND status NOT IN ('Completed', 'Cancelled')
        ORDER BY due_date ASC
        LIMIT 20
    ");
    $stmt->execute([':today' => $today]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $tasks
    ];
}

function getTopProcesses($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                pm.id,
                pm.text as name,
                COUNT(DISTINCT t.task_id) as task_count,
                SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                COUNT(DISTINCT t.task_id) as total_tasks
            FROM process_map pm
            LEFT JOIN process_steps ps ON pm.id = ps.process_id
            LEFT JOIN tasks t ON ps.step_id = t.workflow_step_id
            WHERE pm.type = 'process'
            GROUP BY pm.id, pm.text
            ORDER BY task_count DESC
            LIMIT 10
        ");
        $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate completion rates
        foreach ($processes as &$process) {
            if ($process['total_tasks'] > 0) {
                $process['completion_rate'] = round(($process['completed_tasks'] / $process['total_tasks']) * 100);
            } else {
                $process['completion_rate'] = 0;
            }
        }
        
        return [
            'success' => true,
            'data' => $processes
        ];
    } catch (PDOException $e) {
        // Fallback if process_steps doesn't exist
        $stmt = $pdo->query("
            SELECT 
                id,
                text as name,
                0 as task_count,
                0 as completion_rate
            FROM process_map
            WHERE type = 'process'
            LIMIT 10
        ");
        $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $processes
        ];
    }
}
?>


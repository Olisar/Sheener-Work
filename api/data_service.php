<?php
/* File: sheener/api/data_service.php */

/**
 * Data Service for AI Agent
 * Provides access to backend data for analysis
 */

session_start();
require_once __DIR__ . '/../php/database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $params = $_GET['params'] ?? $_POST['params'] ?? [];
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action is required']);
        exit;
    }
    
    $result = null;
    
    switch ($action) {
        case 'dashboard_stats':
            $result = getDashboardStats($pdo);
            break;
            
        case 'risk_summary':
            $result = getRiskSummary($pdo, $params);
            break;
            
        case 'permit_summary':
            $result = getPermitSummary($pdo, $params);
            break;
            
        case 'incident_summary':
            $result = getIncidentSummary($pdo, $params);
            break;
            
        case 'kpi_data':
            $result = getKPIData($pdo, $params);
            break;
            
        case 'task_summary':
            $result = getTaskSummary($pdo, $params);
            break;
            
        case 'overdue_items':
            $result = getOverdueItems($pdo);
            break;
            
        case 'recent_activity':
            $result = getRecentActivity($pdo, $params);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getDashboardStats($pdo) {
    $stats = [];
    
    // Risk statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total, 
                            SUM(CASE WHEN priority = 'Critical' OR priority = 'Emergency' THEN 1 ELSE 0 END) as critical,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
                            FROM risk_register");
        $stats['risks'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['risks'] = ['error' => 'Unable to fetch risk data'];
    }
    
    // Permit statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) as expired,
                            SUM(CASE WHEN expiry_date < CURDATE() AND status = 'Active' THEN 1 ELSE 0 END) as overdue
                            FROM permits");
        $stats['permits'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['permits'] = ['error' => 'Unable to fetch permit data'];
    }
    
    // Task statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN due_date < CURDATE() AND status != 'Completed' THEN 1 ELSE 0 END) as overdue
                            FROM tasks");
        $stats['tasks'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['tasks'] = ['error' => 'Unable to fetch task data'];
    }
    
    // Event/Incident statistics
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total,
                            SUM(CASE WHEN event_type = 'Incident' THEN 1 ELSE 0 END) as incidents,
                            SUM(CASE WHEN event_type = 'Near Miss' THEN 1 ELSE 0 END) as near_misses
                            FROM events
                            WHERE reported_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stats['events'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stats['events'] = ['error' => 'Unable to fetch event data'];
    }
    
    return $stats;
}

function getRiskSummary($pdo, $params) {
    $where = ['1=1'];
    $queryParams = [];
    
    if (!empty($params['status'])) {
        $where[] = 'status = :status';
        $queryParams[':status'] = $params['status'];
    }
    
    if (!empty($params['priority'])) {
        $where[] = 'priority = :priority';
        $queryParams[':priority'] = $params['priority'];
    }
    
    $sql = "SELECT risk_id, risk_code, risk_title, risk_description, 
                   category_id, priority, status, date_identified, risk_owner
            FROM risk_register 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY date_identified DESC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPermitSummary($pdo, $params) {
    $where = ['1=1'];
    $queryParams = [];
    
    if (!empty($params['status'])) {
        $where[] = 'p.status = :status';
        $queryParams[':status'] = $params['status'];
    }
    
    if (!empty($params['type'])) {
        $where[] = 'p.permit_type = :type';
        $queryParams[':type'] = $params['type'];
    }
    
    $sql = "SELECT p.permit_id, p.permit_type, p.status, p.issue_date, p.expiry_date,
                   t.task_name, 
                   CONCAT(issuer.FirstName, ' ', issuer.LastName) AS issued_by_name
            FROM permits p
            LEFT JOIN tasks t ON p.task_id = t.task_id
            LEFT JOIN people issuer ON p.issued_by = issuer.people_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.issue_date DESC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getIncidentSummary($pdo, $params) {
    $where = ['1=1'];
    $queryParams = [];
    
    if (!empty($params['type'])) {
        $where[] = 'event_type = :type';
        $queryParams[':type'] = $params['type'];
    }
    
    $days = $params['days'] ?? 30;
    $where[] = 'reported_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)';
    $queryParams[':days'] = $days;
    
    $sql = "SELECT event_id, event_type, description, status, reported_date, severity
            FROM events
            WHERE " . implode(' AND ', $where) . "
            ORDER BY reported_date DESC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getKPIData($pdo, $params) {
    $days = $params['days'] ?? 30;
    
    $kpis = [];
    
    // Safety incidents
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, event_type
                              FROM events
                              WHERE reported_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                              GROUP BY event_type");
        $stmt->execute([':days' => $days]);
        $kpis['incidents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['incidents'] = [];
    }
    
    // Permit compliance
    try {
        $stmt = $pdo->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN expiry_date < CURDATE() AND status = 'Active' THEN 1 ELSE 0 END) as expired,
                            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
                            FROM permits");
        $kpis['permits'] = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['permits'] = [];
    }
    
    // Risk distribution
    try {
        $stmt = $pdo->query("SELECT priority, COUNT(*) as count
                            FROM risk_register
                            WHERE status = 'Active'
                            GROUP BY priority");
        $kpis['risks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $kpis['risks'] = [];
    }
    
    return $kpis;
}

function getTaskSummary($pdo, $params) {
    $where = ['1=1'];
    $queryParams = [];
    
    if (!empty($params['status'])) {
        $where[] = 'status = :status';
        $queryParams[':status'] = $params['status'];
    }
    
    $sql = "SELECT task_id, task_name, task_description, status, priority, due_date,
                   CONCAT(p.FirstName, ' ', p.LastName) AS assigned_to_name
            FROM tasks t
            LEFT JOIN people p ON t.assigned_to = p.people_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY due_date ASC
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOverdueItems($pdo) {
    $overdue = [];
    
    // Overdue permits
    try {
        $stmt = $pdo->query("SELECT permit_id, permit_type, expiry_date, status
                            FROM permits
                            WHERE expiry_date < CURDATE() AND status = 'Active'
                            ORDER BY expiry_date ASC
                            LIMIT 10");
        $overdue['permits'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['permits'] = [];
    }
    
    // Overdue tasks
    try {
        $stmt = $pdo->query("SELECT task_id, task_name, due_date, status, priority
                            FROM tasks
                            WHERE due_date < CURDATE() AND status != 'Completed'
                            ORDER BY due_date ASC
                            LIMIT 10");
        $overdue['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['tasks'] = [];
    }
    
    // Risks due for review
    try {
        $stmt = $pdo->query("SELECT risk_id, risk_code, risk_title, next_review_date, priority
                            FROM risk_register
                            WHERE next_review_date < CURDATE() AND status = 'Active'
                            ORDER BY next_review_date ASC
                            LIMIT 10");
        $overdue['risks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $overdue['risks'] = [];
    }
    
    return $overdue;
}

function getRecentActivity($pdo, $params) {
    $days = $params['days'] ?? 7;
    $limit = $params['limit'] ?? 10;
    
    $activities = [];
    
    // Recent permits
    try {
        $stmt = $pdo->prepare("SELECT 'permit' as type, permit_id as id, permit_type as title, issue_date as date
                              FROM permits
                              WHERE issue_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                              ORDER BY issue_date DESC
                              LIMIT :limit");
        $stmt->execute([':days' => $days, ':limit' => $limit]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        // Ignore
    }
    
    // Recent events
    try {
        $stmt = $pdo->prepare("SELECT 'event' as type, event_id as id, event_type as title, reported_date as date
                              FROM events
                              WHERE reported_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                              ORDER BY reported_date DESC
                              LIMIT :limit");
        $stmt->execute([':days' => $days, ':limit' => $limit]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        // Ignore
    }
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($activities, 0, $limit);
}


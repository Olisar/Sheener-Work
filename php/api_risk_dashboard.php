<?php
/* File: sheener/php/api_risk_dashboard.php */

/**
 * Risk Assessment Dashboard API
 * Handles dashboard endpoints: stats, charts, upcoming reviews, recent activity
 */
/* php/api_risk_dashboard.php - updated 2025-12-03 */
session_start();
require_once 'database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
    
    $endpoint = $_GET['endpoint'] ?? 'stats';
    
    switch ($endpoint) {
        case 'stats':
            // Get dashboard statistics
            $stats = [];
            
            // Critical risks
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register WHERE priority = 'Critical' OR priority = 'Emergency'");
            $stats['critical'] = (int)$stmt->fetchColumn();
            
            // High priority
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register WHERE priority = 'High'");
            $stats['high'] = (int)$stmt->fetchColumn();
            
            // Active risks
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register WHERE status = 'Active'");
            $stats['active'] = (int)$stmt->fetchColumn();
            
            // Due reviews
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register WHERE next_review_date <= CURDATE() AND status = 'Active'");
            $stats['dueReviews'] = (int)$stmt->fetchColumn();
            
            // Escalated
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register WHERE status = 'Escalated'");
            $stats['escalated'] = (int)$stmt->fetchColumn();
            
            // Compliant standards
            $stmt = $pdo->query("SELECT COUNT(*) FROM risk_standards_mapping WHERE compliance_status = 'Compliant'");
            $stats['compliant'] = (int)$stmt->fetchColumn();
            
            echo json_encode($stats);
            break;
            
        case 'charts':
            // Get chart data
            $charts = [];
            
            // Status distribution
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count
                FROM risk_register
                GROUP BY status
            ");
            $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $charts['status'] = [
                'labels' => array_column($statusData, 'status'),
                'data' => array_column($statusData, 'count')
            ];
            
            // Priority distribution
            $stmt = $pdo->query("
                SELECT priority, COUNT(*) as count
                FROM risk_register
                GROUP BY priority
            ");
            $priorityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $charts['priority'] = [
                'labels' => array_column($priorityData, 'priority'),
                'data' => array_column($priorityData, 'count')
            ];
            
            // Category distribution
            $stmt = $pdo->query("
                SELECT c.category_name, COUNT(*) as count
                FROM risk_register r
                JOIN risk_categories c ON r.category_id = c.category_id
                GROUP BY c.category_id, c.category_name
                ORDER BY count DESC
                LIMIT 10
            ");
            $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $charts['categories'] = [
                'labels' => array_column($categoryData, 'category_name'),
                'data' => array_column($categoryData, 'count')
            ];
            
            // Compliance status
            $stmt = $pdo->query("
                SELECT compliance_status, COUNT(*) as count
                FROM risk_standards_mapping
                GROUP BY compliance_status
            ");
            $complianceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $charts['compliance'] = [
                'labels' => array_column($complianceData, 'compliance_status'),
                'data' => array_column($complianceData, 'count')
            ];
            
            // Trends (risks by month)
            $stmt = $pdo->query("
                SELECT DATE_FORMAT(date_identified, '%Y-%m') as month, COUNT(*) as count
                FROM risk_register
                WHERE date_identified >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month
            ");
            $trendsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $charts['trends'] = [
                'labels' => array_column($trendsData, 'month'),
                'datasets' => [[
                    'label' => 'Risks Identified',
                    'data' => array_column($trendsData, 'count'),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)'
                ]]
            ];
            
            echo json_encode($charts);
            break;
            
        case 'upcoming-reviews':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $stmt = $pdo->prepare("
                SELECT r.risk_id, r.risk_code, r.risk_title, r.next_review_date
                FROM risk_register r
                WHERE r.next_review_date IS NOT NULL
                  AND r.status = 'Active'
                ORDER BY r.next_review_date ASC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'recent-activity':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            // Combine recent activities from different sources
            $activities = [];
            
            // Recent risks
            $stmt = $pdo->prepare("
                SELECT 'risk_created' as type, CONCAT('Risk ', risk_code, ' created') as description, created_at as date
                FROM risk_register
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
            
            // Recent reviews
            $stmt = $pdo->prepare("
                SELECT 'review_completed' as type, CONCAT('Review completed') as description, created_at as date
                FROM risk_reviews
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
            
            // Sort by date and limit
            usort($activities, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            echo json_encode(array_slice($activities, 0, $limit));
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Dashboard endpoint not found']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


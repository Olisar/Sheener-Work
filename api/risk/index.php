<?php
/* File: sheener/api/risk/index.php */

/**
 * Risk Assessment RESTful API
 * Handles all /api/risk/* endpoints
 */

session_start();
require_once __DIR__ . '/../../php/database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Parse the request path
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Get the path from REQUEST_URI
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // Remove query string if present
    $path = strtok($path, '?');
    
    // Get the directory of the script (api/risk/)
    $scriptDir = dirname($scriptName);
    
    // Remove the script directory from the path
    if (strpos($path, $scriptDir) === 0) {
        $path = substr($path, strlen($scriptDir));
    }
    
    // Clean up the path
    $path = trim($path, '/');
    $pathParts = array_filter(explode('/', $path));
    $pathParts = array_values($pathParts);
    
    // Remove 'api' and 'risk' if they're in the path
    if (isset($pathParts[0]) && $pathParts[0] === 'api') array_shift($pathParts);
    if (isset($pathParts[0]) && $pathParts[0] === 'risk') array_shift($pathParts);
    
    $resource = $pathParts[0] ?? '';
    $id = $pathParts[1] ?? null;
    $subResource = $pathParts[2] ?? null;
    
    // Debug logging (remove in production)
    // error_log("Path: $path, Resource: $resource, ID: $id, SubResource: $subResource");
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = [];
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Route the request
    switch ($resource) {
        case 'register':
            handleRiskRegister($pdo, $method, $id, $input, $_GET);
            break;
            
        case 'reviews':
            handleRiskReviews($pdo, $method, $id, $input, $_GET);
            break;
            
        case 'standards':
            handleStandardsMapping($pdo, $method, $id, $input, $_GET);
            break;
            
        case 'dashboard':
            handleDashboard($pdo, $subResource, $_GET);
            break;
            
        case 'lookup':
            handleLookup($pdo, $subResource, $id, $_GET);
            break;
            
        case 'export':
            handleExport($pdo, $_GET);
            break;
            
        case '':
            // Root path - return API info
            echo json_encode([
                'success' => true,
                'message' => 'Risk Assessment API',
                'endpoints' => [
                    'GET /api/risk/register' => 'List risks',
                    'GET /api/risk/register/{id}' => 'Get risk',
                    'GET /api/risk/lookup/categories' => 'Get categories',
                    'GET /api/risk/lookup/people' => 'Get people',
                    'GET /api/risk/lookup/standards' => 'Get standards',
                    'GET /api/risk/dashboard/stats' => 'Get dashboard stats'
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'error' => 'Endpoint not found',
                'resource' => $resource,
                'path' => $path,
                'pathParts' => $pathParts
            ]);
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

// ============================================================================
// Risk Register Handlers
// ============================================================================

function handleRiskRegister($pdo, $method, $id, $input, $query) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single risk
                $stmt = $pdo->prepare("
                    SELECT r.*, 
                           c.category_name,
                           sc.category_name AS subcategory_name
                    FROM risk_register r
                    LEFT JOIN risk_categories c ON r.category_id = c.category_id
                    LEFT JOIN risk_categories sc ON r.subcategory_id = sc.category_id
                    WHERE r.risk_id = :id
                ");
                $stmt->execute([':id' => $id]);
                $risk = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$risk) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Risk not found']);
                    return;
                }
                
                echo json_encode($risk);
            } else {
                // Get list of risks with filters
                $where = ['1=1'];
                $params = [];
                
                if (!empty($query['status'])) {
                    $statuses = is_array($query['status']) ? $query['status'] : [$query['status']];
                    $placeholders = [];
                    foreach ($statuses as $idx => $status) {
                        $key = ':status_' . $idx;
                        $placeholders[] = $key;
                        $params[$key] = $status;
                    }
                    $where[] = 'r.status IN (' . implode(',', $placeholders) . ')';
                }
                
                if (!empty($query['priority'])) {
                    $priorities = is_array($query['priority']) ? $query['priority'] : [$query['priority']];
                    $placeholders = [];
                    foreach ($priorities as $idx => $priority) {
                        $key = ':priority_' . $idx;
                        $placeholders[] = $key;
                        $params[$key] = $priority;
                    }
                    $where[] = 'r.priority IN (' . implode(',', $placeholders) . ')';
                }
                
                if (!empty($query['category_id'])) {
                    $where[] = 'r.category_id = :category_id';
                    $params[':category_id'] = $query['category_id'];
                }
                
                if (!empty($query['risk_owner'])) {
                    $where[] = 'r.risk_owner = :risk_owner';
                    $params[':risk_owner'] = $query['risk_owner'];
                }
                
                if (!empty($query['search'])) {
                    $where[] = '(r.risk_code LIKE :search OR r.risk_title LIKE :search OR r.risk_description LIKE :search)';
                    $params[':search'] = '%' . $query['search'] . '%';
                }
                
                $sql = "
                    SELECT r.*, 
                           c.category_name,
                           sc.category_name AS subcategory_name
                    FROM risk_register r
                    LEFT JOIN risk_categories c ON r.category_id = c.category_id
                    LEFT JOIN risk_categories sc ON r.subcategory_id = sc.category_id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY r.risk_id DESC
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $risks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode($risks);
            }
            break;
            
        case 'POST':
            // Create risk
            $required = ['risk_code', 'risk_title', 'risk_description', 'category_id', 'date_identified', 'identified_by'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => "Field required: $field"]);
                    return;
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO risk_register (
                    risk_code, risk_title, risk_description, category_id, subcategory_id,
                    risk_source, date_identified, identified_by, risk_owner,
                    lifecycle_stage, product_line, site_location,
                    status, priority, review_frequency, next_review_date,
                    approval_status, approved_by, approval_date, version, created_by
                ) VALUES (
                    :risk_code, :risk_title, :risk_description, :category_id, :subcategory_id,
                    :risk_source, :date_identified, :identified_by, :risk_owner,
                    :lifecycle_stage, :product_line, :site_location,
                    :status, :priority, :review_frequency, :next_review_date,
                    :approval_status, :approved_by, :approval_date, :version, :created_by
                )
            ");
            
            $stmt->execute([
                ':risk_code' => $input['risk_code'],
                ':risk_title' => $input['risk_title'],
                ':risk_description' => $input['risk_description'],
                ':category_id' => $input['category_id'],
                ':subcategory_id' => $input['subcategory_id'] ?? null,
                ':risk_source' => $input['risk_source'] ?? 'Other',
                ':date_identified' => $input['date_identified'],
                ':identified_by' => $input['identified_by'],
                ':risk_owner' => $input['risk_owner'] ?? null,
                ':lifecycle_stage' => $input['lifecycle_stage'] ?? 'All Stages',
                ':product_line' => $input['product_line'] ?? null,
                ':site_location' => $input['site_location'] ?? null,
                ':status' => $input['status'] ?? 'Active',
                ':priority' => $input['priority'] ?? 'Medium',
                ':review_frequency' => $input['review_frequency'] ?? 'Quarterly',
                ':next_review_date' => $input['next_review_date'] ?? null,
                ':approval_status' => $input['approval_status'] ?? 'Draft',
                ':approved_by' => $input['approved_by'] ?? null,
                ':approval_date' => $input['approval_date'] ?? null,
                ':version' => $input['version'] ?? 1,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'data' => ['risk_id' => $pdo->lastInsertId()]]);
            break;
            
        case 'PUT':
            // Update risk
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Risk ID required']);
                return;
            }
            
            $fields = [];
            $params = [':id' => $id];
            
            $allowed = ['risk_code', 'risk_title', 'risk_description', 'category_id', 'subcategory_id',
                        'risk_source', 'date_identified', 'identified_by', 'risk_owner',
                        'lifecycle_stage', 'product_line', 'site_location',
                        'status', 'priority', 'review_frequency', 'next_review_date',
                        'approval_status', 'approved_by', 'approval_date', 'version'];
            
            foreach ($allowed as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                return;
            }
            
            $sql = "UPDATE risk_register SET " . implode(', ', $fields) . " WHERE risk_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            // Delete risk
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Risk ID required']);
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM risk_register WHERE risk_id = :id");
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
    }
}

// ============================================================================
// Risk Reviews Handlers
// ============================================================================

function handleRiskReviews($pdo, $method, $id, $input, $query) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single review
                $stmt = $pdo->prepare("SELECT * FROM risk_reviews WHERE review_id = :id");
                $stmt->execute([':id' => $id]);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$review) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Review not found']);
                    return;
                }
                
                echo json_encode($review);
            } else if (!empty($query['risk_id'])) {
                // Get reviews for a risk
                $stmt = $pdo->prepare("SELECT * FROM risk_reviews WHERE risk_id = :risk_id ORDER BY review_date DESC");
                $stmt->execute([':risk_id' => $query['risk_id']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Get all reviews with filters
                $where = ['1=1'];
                $params = [];
                
                if (!empty($query['status'])) {
                    $where[] = 'risk_status = :status';
                    $params[':status'] = $query['status'];
                }
                
                $sql = "SELECT * FROM risk_reviews WHERE " . implode(' AND ', $where) . " ORDER BY review_date DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            // Create review
            $required = ['risk_id', 'review_date', 'reviewer', 'risk_status'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => "Field required: $field"]);
                    return;
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO risk_reviews (
                    risk_id, review_date, review_type, review_outcome,
                    action_items, review_approved_by, review_approval_date,
                    reviewer, risk_status, status_change_rationale,
                    next_review_date, escalation_required, escalated_to, review_notes
                ) VALUES (
                    :risk_id, :review_date, :review_type, :review_outcome,
                    :action_items, :review_approved_by, :review_approval_date,
                    :reviewer, :risk_status, :status_change_rationale,
                    :next_review_date, :escalation_required, :escalated_to, :review_notes
                )
            ");
            
            $stmt->execute([
                ':risk_id' => $input['risk_id'],
                ':review_date' => $input['review_date'],
                ':review_type' => $input['review_type'] ?? 'Scheduled',
                ':review_outcome' => $input['review_outcome'] ?? 'No Change',
                ':action_items' => $input['action_items'] ?? null,
                ':review_approved_by' => $input['review_approved_by'] ?? null,
                ':review_approval_date' => $input['review_approval_date'] ?? null,
                ':reviewer' => $input['reviewer'],
                ':risk_status' => $input['risk_status'],
                ':status_change_rationale' => $input['status_change_rationale'] ?? null,
                ':next_review_date' => $input['next_review_date'] ?? null,
                ':escalation_required' => $input['escalation_required'] ?? 'No',
                ':escalated_to' => $input['escalated_to'] ?? null,
                ':review_notes' => $input['review_notes'] ?? null
            ]);
            
            // Update risk status
            $updateStmt = $pdo->prepare("UPDATE risk_register SET status = :status, next_review_date = :next_review_date WHERE risk_id = :risk_id");
            $updateStmt->execute([
                ':status' => $input['risk_status'],
                ':next_review_date' => $input['next_review_date'] ?? null,
                ':risk_id' => $input['risk_id']
            ]);
            
            echo json_encode(['success' => true, 'data' => ['review_id' => $pdo->lastInsertId()]]);
            break;
            
        case 'PUT':
            // Update review
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Review ID required']);
                return;
            }
            
            $fields = [];
            $params = [':id' => $id];
            
            $allowed = ['review_date', 'review_type', 'review_outcome', 'action_items',
                        'review_approved_by', 'review_approval_date', 'reviewer', 'risk_status',
                        'status_change_rationale', 'next_review_date', 'escalation_required',
                        'escalated_to', 'review_notes'];
            
            foreach ($allowed as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                return;
            }
            
            $sql = "UPDATE risk_reviews SET " . implode(', ', $fields) . " WHERE review_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            // Delete review
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Review ID required']);
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM risk_reviews WHERE review_id = :id");
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
    }
}

// ============================================================================
// Standards Mapping Handlers
// ============================================================================

function handleStandardsMapping($pdo, $method, $id, $input, $query) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single mapping
                $stmt = $pdo->prepare("SELECT * FROM risk_standards_mapping WHERE mapping_id = :id");
                $stmt->execute([':id' => $id]);
                $mapping = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$mapping) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Mapping not found']);
                    return;
                }
                
                echo json_encode($mapping);
            } else if (!empty($query['risk_id'])) {
                // Get standards for a risk
                $stmt = $pdo->prepare("
                    SELECT rsm.*, rs.standard_name, rs.standard_code
                    FROM risk_standards_mapping rsm
                    JOIN regulatory_standards rs ON rsm.standard_id = rs.standard_id
                    WHERE rsm.risk_id = :risk_id
                ");
                $stmt->execute([':risk_id' => $query['risk_id']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Get all mappings
                $stmt = $pdo->query("
                    SELECT rsm.*, rs.standard_name, r.risk_code
                    FROM risk_standards_mapping rsm
                    JOIN regulatory_standards rs ON rsm.standard_id = rs.standard_id
                    JOIN risk_register r ON rsm.risk_id = r.risk_id
                    ORDER BY rsm.created_at DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;
            
        case 'POST':
            // Create mapping
            $required = ['risk_id', 'standard_id', 'relevance_level'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => "Field required: $field"]);
                    return;
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO risk_standards_mapping (
                    risk_id, standard_id, relevance_level,
                    applicable_sections, compliance_status, notes, created_by
                ) VALUES (
                    :risk_id, :standard_id, :relevance_level,
                    :applicable_sections, :compliance_status, :notes, :created_by
                )
            ");
            
            $stmt->execute([
                ':risk_id' => $input['risk_id'],
                ':standard_id' => $input['standard_id'],
                ':relevance_level' => $input['relevance_level'],
                ':applicable_sections' => $input['applicable_sections'] ?? null,
                ':compliance_status' => $input['compliance_status'] ?? 'Under Review',
                ':notes' => $input['notes'] ?? null,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'data' => ['mapping_id' => $pdo->lastInsertId()]]);
            break;
            
        case 'PUT':
            // Update mapping
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mapping ID required']);
                return;
            }
            
            $fields = [];
            $params = [':id' => $id];
            
            $allowed = ['relevance_level', 'applicable_sections', 'compliance_status', 'notes'];
            
            foreach ($allowed as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No fields to update']);
                return;
            }
            
            $sql = "UPDATE risk_standards_mapping SET " . implode(', ', $fields) . " WHERE mapping_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'DELETE':
            // Delete mapping
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mapping ID required']);
                return;
            }
            
            $stmt = $pdo->prepare("DELETE FROM risk_standards_mapping WHERE mapping_id = :id");
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true]);
            break;
    }
}

// ============================================================================
// Dashboard Handlers
// ============================================================================

function handleDashboard($pdo, $subResource, $query) {
    switch ($subResource) {
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
            $limit = isset($query['limit']) ? (int)$query['limit'] : 10;
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
            $limit = isset($query['limit']) ? (int)$query['limit'] : 10;
            // Combine recent activities from different sources
            $activities = [];
            
            // Recent risks
            $stmt = $pdo->prepare("
                SELECT 'risk_created' as type, risk_code as description, created_at as date
                FROM risk_register
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
            
            // Recent reviews
            $stmt = $pdo->prepare("
                SELECT 'review_completed' as type, CONCAT('Review completed for risk') as description, created_at as date
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
}

// ============================================================================
// Lookup Handlers
// ============================================================================

function handleLookup($pdo, $resource, $id, $query) {
    switch ($resource) {
        case 'categories':
            $stmt = $pdo->query("
                SELECT category_id, category_name, category_description,
                       parent_category_id, category_level, category_path
                FROM risk_categories
                WHERE is_active = 1
                ORDER BY parent_category_id IS NULL DESC, category_name
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'subcategories':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Category ID required']);
                return;
            }
            $stmt = $pdo->prepare("
                SELECT category_id, category_name, category_description
                FROM risk_categories
                WHERE parent_category_id = :id AND is_active = 1
                ORDER BY category_name
            ");
            $stmt->execute([':id' => $id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'people':
            $stmt = $pdo->query("
                SELECT people_id, 
                       COALESCE(CONCAT(FirstName, ' ', LastName), email, CONCAT('Person ', people_id)) as name,
                       FirstName as first_name, 
                       LastName as last_name, 
                       email
                FROM people
                WHERE IsActive = 1
                ORDER BY LastName, FirstName
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        case 'standards':
            $stmt = $pdo->query("
                SELECT standard_id, standard_name, standard_code, regulatory_body, standard_type
                FROM regulatory_standards
                WHERE is_active = 1
                ORDER BY regulatory_body, standard_name
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Lookup resource not found']);
    }
}

// ============================================================================
// Export Handler
// ============================================================================

function handleExport($pdo, $query) {
    // For now, just return a message
    // In production, you would generate and return a PDF file
    echo json_encode([
        'success' => true,
        'message' => 'Export functionality to be implemented',
        'format' => $query['format'] ?? 'pdf'
    ]);
}


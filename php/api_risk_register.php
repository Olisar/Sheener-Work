<?php
/* File: sheener/php/api_risk_register.php */

/**
 * Risk Register API
 * Handles CRUD operations for risk register management system
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Read POST body once
    $rawInput = file_get_contents('php://input');
    $postInput = !empty($rawInput) ? json_decode($rawInput, true) : [];
    
    // Get action from GET or POST body
    $action = $_GET['action'] ?? $postInput['action'] ?? 'list';
    $id = $_GET['id'] ?? $postInput['id'] ?? null;
    
    switch ($action) {
        // ====================================================================
        // Risk Categories
        // ====================================================================
        case 'list_categories':
            $response = listRiskCategories($pdo);
            break;
            
        case 'get_category':
            if (!$id) {
                throw new Exception('Category ID required');
            }
            $response = getRiskCategory($pdo, $id);
            break;
            
        case 'create_category':
            $response = createRiskCategory($pdo, $postInput);
            break;
            
        case 'update_category':
            if (!$id) {
                throw new Exception('Category ID required');
            }
            $response = updateRiskCategory($pdo, $id, $postInput);
            break;
            
        case 'delete_category':
            if (!$id) {
                throw new Exception('Category ID required');
            }
            $response = deleteRiskCategory($pdo, $id);
            break;
            
        // ====================================================================
        // Risk Register
        // ====================================================================
        case 'list':
        case 'list_risks':
            $response = listRisks($pdo, $_GET);
            break;
            
        case 'get_risk':
        case 'detail':
            if (!$id) {
                throw new Exception('Risk ID required');
            }
            $response = getRiskDetail($pdo, $id);
            break;
            
        case 'create_risk':
            $response = createRisk($pdo, $postInput);
            break;
            
        case 'update_risk':
            if (!$id) {
                throw new Exception('Risk ID required');
            }
            $response = updateRisk($pdo, $id, $postInput);
            break;
            
        case 'delete_risk':
            if (!$id) {
                throw new Exception('Risk ID required');
            }
            $response = deleteRisk($pdo, $id);
            break;
            
        // ====================================================================
        // Risk Assessment
        // ====================================================================
        case 'list_assessments':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            if (!$riskId) {
                throw new Exception('Risk ID required');
            }
            $response = listRiskAssessments($pdo, $riskId);
            break;
            
        case 'get_assessment':
            if (!$id) {
                throw new Exception('Assessment ID required');
            }
            $response = getRiskAssessment($pdo, $id);
            break;
            
        case 'create_assessment':
            $response = createRiskAssessment($pdo, $postInput);
            break;
            
        case 'update_assessment':
            if (!$id) {
                throw new Exception('Assessment ID required');
            }
            $response = updateRiskAssessment($pdo, $id, $postInput);
            break;
            
        // ====================================================================
        // Risk Controls
        // ====================================================================
        case 'list_controls':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            if (!$riskId) {
                throw new Exception('Risk ID required');
            }
            $response = listRiskControls($pdo, $riskId);
            break;
            
        case 'create_control':
            $response = createRiskControl($pdo, $postInput);
            break;
            
        case 'update_control':
            if (!$id) {
                throw new Exception('Control ID required');
            }
            $response = updateRiskControl($pdo, $id, $postInput);
            break;
            
        case 'delete_control':
            if (!$id) {
                throw new Exception('Control ID required');
            }
            $response = deleteRiskControl($pdo, $id);
            break;
            
        // ====================================================================
        // Regulatory Requirements
        // ====================================================================
        case 'list_requirements':
            $response = listRegulatoryRequirements($pdo, $_GET);
            break;
            
        case 'create_requirement':
            $response = createRegulatoryRequirement($pdo, $postInput);
            break;
            
        case 'update_requirement':
            if (!$id) {
                throw new Exception('Requirement ID required');
            }
            $response = updateRegulatoryRequirement($pdo, $id, $postInput);
            break;
            
        // ====================================================================
        // Risk-Regulatory Mapping
        // ====================================================================
        case 'list_risk_requirements':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            if (!$riskId) {
                throw new Exception('Risk ID required');
            }
            $response = listRiskRegulatoryMapping($pdo, $riskId);
            break;
            
        case 'map_risk_requirement':
            $response = mapRiskToRequirement($pdo, $postInput);
            break;
            
        // ====================================================================
        // Risk Incidents
        // ====================================================================
        case 'list_incidents':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            $response = listRiskIncidents($pdo, $riskId);
            break;
            
        case 'create_incident':
            $response = createRiskIncident($pdo, $postInput);
            break;
            
        // ====================================================================
        // Risk Reviews
        // ====================================================================
        case 'list_reviews':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            if (!$riskId) {
                throw new Exception('Risk ID required');
            }
            $response = listRiskReviews($pdo, $riskId);
            break;
            
        case 'create_review':
            $response = createRiskReview($pdo, $postInput);
            break;
            
        // ====================================================================
        // Risk KPIs
        // ====================================================================
        case 'list_kpis':
            $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
            if (!$riskId) {
                throw new Exception('Risk ID required');
            }
            $response = listRiskKPIs($pdo, $riskId);
            break;
            
        case 'create_kpi':
            $response = createRiskKPI($pdo, $postInput);
            break;
            
        case 'update_kpi':
            if (!$id) {
                throw new Exception('KPI ID required');
            }
            $response = updateRiskKPI($pdo, $id, $postInput);
            break;
            
        // ====================================================================
        // Process Map Integration
        // ====================================================================
        case 'link_to_process':
            $response = linkRiskToProcess($pdo, $postInput);
            break;
            
        case 'unlink_from_process':
            $response = unlinkRiskFromProcess($pdo, $postInput);
            break;
            
        case 'get_process_risks':
            $processId = $_GET['process_id'] ?? $postInput['process_id'] ?? null;
            if (!$processId) {
                throw new Exception('Process ID required');
            }
            $response = getProcessRisks($pdo, $processId);
            break;
            
        // ====================================================================
        // Dashboard/Statistics
        // ====================================================================
        case 'dashboard_stats':
            $response = getDashboardStats($pdo);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ============================================================================
// Risk Categories Functions
// ============================================================================

function listRiskCategories($pdo) {
    // First, get all categories
    $stmt = $pdo->query("
        SELECT 
            c.category_id,
            c.category_name,
            c.category_description,
            c.iso_standard_link,
            c.parent_category_id,
            c.is_active,
            c.created_at,
            c.category_level,
            c.category_path,
            p.category_name AS parent_category_name,
            (SELECT COUNT(DISTINCT r.risk_id) 
             FROM risk_register r 
             WHERE r.category_id = c.category_id OR r.subcategory_id = c.category_id) AS risk_count,
            (SELECT COUNT(*) 
             FROM risk_categories sc 
             WHERE sc.parent_category_id = c.category_id) AS subcategory_count
        FROM risk_categories c
        LEFT JOIN risk_categories p ON c.parent_category_id = p.category_id
        WHERE c.is_active = 1
        ORDER BY c.parent_category_id IS NULL DESC, c.parent_category_id, c.category_name
    ");
    
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Remove duplicates by keeping the one with the most risks or lowest ID
    $uniqueCategories = [];
    $seenNames = [];
    
    foreach ($allCategories as $category) {
        $nameKey = strtolower(trim($category['category_name']));
        
        // For parent categories, keep only one
        if (!$category['parent_category_id']) {
            if (!isset($seenNames[$nameKey])) {
                $seenNames[$nameKey] = $category;
            } else {
                // If duplicate found, keep the one with more risks, or lower ID if equal
                $existing = $seenNames[$nameKey];
                if ($category['risk_count'] > $existing['risk_count'] || 
                    ($category['risk_count'] == $existing['risk_count'] && $category['category_id'] < $existing['category_id'])) {
                    $seenNames[$nameKey] = $category;
                }
            }
        } else {
            // For subcategories, include all (they have unique parent relationships)
            $uniqueCategories[] = $category;
        }
    }
    
    // Add unique parent categories
    foreach ($seenNames as $category) {
        $uniqueCategories[] = $category;
    }
    
    // Sort again
    usort($uniqueCategories, function($a, $b) {
        if ($a['parent_category_id'] != $b['parent_category_id']) {
            return ($a['parent_category_id'] === null) ? -1 : 1;
        }
        return strcmp($a['category_name'], $b['category_name']);
    });
    
    return [
        'success' => true,
        'data' => $uniqueCategories
    ];
}

function getRiskCategory($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            p.category_name AS parent_category_name
        FROM risk_categories c
        LEFT JOIN risk_categories p ON c.parent_category_id = p.category_id
        WHERE c.category_id = :id
    ");
    $stmt->execute([':id' => $id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception('Category not found');
    }
    
    return [
        'success' => true,
        'data' => $category
    ];
}

function createRiskCategory($pdo, $data) {
    $required = ['category_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_categories (
            category_name, category_description, iso_standard_link,
            parent_category_id, created_by
        ) VALUES (
            :category_name, :category_description, :iso_standard_link,
            :parent_category_id, :created_by
        )
    ");
    
    $stmt->execute([
        ':category_name' => $data['category_name'],
        ':category_description' => $data['category_description'] ?? null,
        ':iso_standard_link' => $data['iso_standard_link'] ?? null,
        ':parent_category_id' => $data['parent_category_id'] ?? null,
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['category_id' => $pdo->lastInsertId()]
    ];
}

function updateRiskCategory($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['category_name', 'category_description', 'iso_standard_link', 'parent_category_id', 'is_active'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_categories SET " . implode(', ', $fields) . " WHERE category_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

function deleteRiskCategory($pdo, $id) {
    // Check if category has risks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM risk_register WHERE category_id = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete category with associated risks');
    }
    
    // Soft delete
    $stmt = $pdo->prepare("UPDATE risk_categories SET is_active = 0 WHERE category_id = :id");
    $stmt->execute([':id' => $id]);
    
    return ['success' => true];
}

// ============================================================================
// Risk Register Functions
// ============================================================================

function listRisks($pdo, $filters = []) {
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['category_id'])) {
        // Filter by category_id - include both direct category matches and subcategory matches
        // Get all subcategory IDs for this category first
        $subcatStmt = $pdo->prepare("SELECT category_id FROM risk_categories WHERE parent_category_id = :cat_id");
        $subcatStmt->execute([':cat_id' => $filters['category_id']]);
        $subcatIds = $subcatStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($subcatIds)) {
            // Build IN clause for subcategories
            $placeholders = [];
            foreach ($subcatIds as $idx => $subcatId) {
                $key = ':subcat_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $subcatId;
            }
            $where[] = '(r.category_id = :category_id OR r.subcategory_id IN (' . implode(',', $placeholders) . '))';
        } else {
            // No subcategories, just match direct category
            $where[] = 'r.category_id = :category_id';
        }
        $params[':category_id'] = $filters['category_id'];
    }
    
    if (!empty($filters['status'])) {
        $where[] = 'r.status = :status';
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['lifecycle_stage'])) {
        $where[] = 'r.lifecycle_stage = :lifecycle_stage';
        $params[':lifecycle_stage'] = $filters['lifecycle_stage'];
    }
    
    if (!empty($filters['search'])) {
        // Search in risk fields AND category names
        $where[] = '(r.risk_title LIKE :search OR r.risk_code LIKE :search OR r.risk_description LIKE :search 
                     OR c.category_name LIKE :search OR sc.category_name LIKE :search)';
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $sql = "
        SELECT 
            r.risk_id,
            r.risk_code,
            r.risk_title,
            r.risk_description,
            r.category_id,
            c.category_name,
            r.subcategory_id,
            sc.category_name AS subcategory_name,
            r.risk_source,
            r.date_identified,
            r.identified_by,
            CONCAT(p.FirstName, ' ', p.LastName) AS identified_by_name,
            r.risk_owner,
            CONCAT(po.FirstName, ' ', po.LastName) AS risk_owner_name,
            po.email AS risk_owner_email,
            r.lifecycle_stage,
            r.product_line,
            r.site_location,
            r.status,
            r.priority,
            r.review_frequency,
            r.next_review_date,
            r.approval_status,
            r.approved_by,
            r.approval_date,
            r.version,
            r.created_at,
            r.updated_at,
            (SELECT MAX(ra.inherent_risk_score) 
             FROM risk_assessment ra 
             WHERE ra.risk_id = r.risk_id) AS current_risk_score,
            (SELECT ra.inherent_risk_level 
             FROM risk_assessment ra 
             WHERE ra.risk_id = r.risk_id 
             ORDER BY ra.assessment_date DESC LIMIT 1) AS current_risk_level
        FROM risk_register r
        LEFT JOIN risk_categories c ON r.category_id = c.category_id
        LEFT JOIN risk_categories sc ON r.subcategory_id = sc.category_id
        LEFT JOIN people p ON r.identified_by = p.people_id
        LEFT JOIN people po ON r.risk_owner = po.people_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.date_identified DESC, r.risk_id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function getRiskDetail($pdo, $id) {
    // Get risk basic info
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            c.category_name,
            sc.category_name AS subcategory_name,
            CONCAT(p.FirstName, ' ', p.LastName) AS identified_by_name
        FROM risk_register r
        LEFT JOIN risk_categories c ON r.category_id = c.category_id
        LEFT JOIN risk_categories sc ON r.subcategory_id = sc.category_id
        LEFT JOIN people p ON r.identified_by = p.people_id
        WHERE r.risk_id = :id
    ");
    $stmt->execute([':id' => $id]);
    $risk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$risk) {
        throw new Exception('Risk not found');
    }
    
    // Get latest assessment
    $stmt = $pdo->prepare("
        SELECT * FROM risk_assessment 
        WHERE risk_id = :id 
        ORDER BY assessment_date DESC LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $risk['latest_assessment'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get controls
    $stmt = $pdo->prepare("
        SELECT 
            rc.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS owner_name
        FROM risk_controls rc
        LEFT JOIN people p ON rc.responsible_owner = p.people_id
        WHERE rc.risk_id = :id
        ORDER BY rc.implementation_status, rc.control_id
    ");
    $stmt->execute([':id' => $id]);
    $risk['controls'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get linked processes
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.text,
            pm.type
        FROM process_map_risk_register pmrr
        JOIN process_map pm ON pmrr.process_map_id = pm.id
        WHERE pmrr.risk_id = :id
    ");
    $stmt->execute([':id' => $id]);
    $risk['linked_processes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => $risk
    ];
}

function createRisk($pdo, $data) {
    $required = ['risk_title', 'risk_description', 'category_id', 'date_identified', 'identified_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    // Generate risk code if not provided
    if (empty($data['risk_code'])) {
        $category = $pdo->prepare("SELECT category_name FROM risk_categories WHERE category_id = :id");
        $category->execute([':id' => $data['category_id']]);
        $catName = $category->fetchColumn();
        $prefix = strtoupper(substr($catName, 0, 3));
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM risk_register WHERE risk_code LIKE :prefix");
        $stmt->execute([':prefix' => $prefix . '-%']);
        $count = $stmt->fetchColumn();
        $data['risk_code'] = $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_register (
            risk_code, risk_title, risk_description, category_id, subcategory_id,
            risk_source, date_identified, identified_by, lifecycle_stage,
            product_line, site_location, status, priority, review_frequency,
            next_review_date, approval_status, created_by
        ) VALUES (
            :risk_code, :risk_title, :risk_description, :category_id, :subcategory_id,
            :risk_source, :date_identified, :identified_by, :lifecycle_stage,
            :product_line, :site_location, :status, :priority, :review_frequency,
            :next_review_date, :approval_status, :created_by
        )
    ");
    
    $stmt->execute([
        ':risk_code' => $data['risk_code'],
        ':risk_title' => $data['risk_title'],
        ':risk_description' => $data['risk_description'],
        ':category_id' => $data['category_id'],
        ':subcategory_id' => $data['subcategory_id'] ?? null,
        ':risk_source' => $data['risk_source'] ?? 'Other',
        ':date_identified' => $data['date_identified'],
        ':identified_by' => $data['identified_by'],
        ':lifecycle_stage' => $data['lifecycle_stage'] ?? 'All Stages',
        ':product_line' => $data['product_line'] ?? null,
        ':site_location' => $data['site_location'] ?? null,
        ':status' => $data['status'] ?? 'Active',
        ':priority' => $data['priority'] ?? 'Medium',
        ':review_frequency' => $data['review_frequency'] ?? 'Quarterly',
        ':next_review_date' => $data['next_review_date'] ?? null,
        ':approval_status' => $data['approval_status'] ?? 'Draft',
        ':created_by' => $_SESSION['user_id'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['risk_id' => $pdo->lastInsertId(), 'risk_code' => $data['risk_code']]
    ];
}

function updateRisk($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['risk_title', 'risk_description', 'category_id', 'subcategory_id', 
                'risk_source', 'lifecycle_stage', 'product_line', 'site_location', 'status',
                'priority', 'review_frequency', 'next_review_date', 'approval_status'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_register SET " . implode(', ', $fields) . " WHERE risk_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

function deleteRisk($pdo, $id) {
    // Check for dependencies
    $checks = [
        'risk_assessment' => 'assessment_id',
        'risk_controls' => 'control_id',
        'risk_incidents' => 'incident_id',
        'risk_reviews' => 'review_id'
    ];
    
    foreach ($checks as $table => $col) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE risk_id = :id");
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete risk with associated $table records");
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM risk_register WHERE risk_id = :id");
    $stmt->execute([':id' => $id]);
    
    return ['success' => true];
}

// ============================================================================
// Risk Assessment Functions
// ============================================================================

function listRiskAssessments($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            ra.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS assessed_by_name
        FROM risk_assessment ra
        LEFT JOIN people p ON ra.assessed_by = p.people_id
        WHERE ra.risk_id = :risk_id
        ORDER BY ra.assessment_date DESC
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function getRiskAssessment($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT 
            ra.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS assessed_by_name
        FROM risk_assessment ra
        LEFT JOIN people p ON ra.assessed_by = p.people_id
        WHERE ra.assessment_id = :id
    ");
    $stmt->execute([':id' => $id]);
    $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assessment) {
        throw new Exception('Assessment not found');
    }
    
    return [
        'success' => true,
        'data' => $assessment
    ];
}

function createRiskAssessment($pdo, $data) {
    $required = ['risk_id', 'assessment_date', 'assessed_by', 'inherent_likelihood', 'inherent_severity'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_assessment (
            risk_id, assessment_date, assessed_by,
            inherent_likelihood, inherent_severity,
            residual_likelihood, residual_severity,
            risk_acceptability, assessment_methodology, notes
        ) VALUES (
            :risk_id, :assessment_date, :assessed_by,
            :inherent_likelihood, :inherent_severity,
            :residual_likelihood, :residual_severity,
            :risk_acceptability, :assessment_methodology, :notes
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':assessment_date' => $data['assessment_date'],
        ':assessed_by' => $data['assessed_by'],
        ':inherent_likelihood' => $data['inherent_likelihood'],
        ':inherent_severity' => $data['inherent_severity'],
        ':residual_likelihood' => $data['residual_likelihood'] ?? null,
        ':residual_severity' => $data['residual_severity'] ?? null,
        ':risk_acceptability' => $data['risk_acceptability'] ?? 'Unacceptable',
        ':assessment_methodology' => $data['assessment_methodology'] ?? 'FMEA',
        ':notes' => $data['notes'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['assessment_id' => $pdo->lastInsertId()]
    ];
}

function updateRiskAssessment($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['inherent_likelihood', 'inherent_severity', 'residual_likelihood', 
                'residual_severity', 'risk_acceptability', 'assessment_methodology', 'notes'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_assessment SET " . implode(', ', $fields) . " WHERE assessment_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

// ============================================================================
// Risk Controls Functions
// ============================================================================

function listRiskControls($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rc.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS owner_name
        FROM risk_controls rc
        LEFT JOIN people p ON rc.responsible_owner = p.people_id
        WHERE rc.risk_id = :risk_id
        ORDER BY rc.implementation_status, rc.control_id
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function createRiskControl($pdo, $data) {
    $required = ['risk_id', 'control_type', 'control_description', 'responsible_owner'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_controls (
            risk_id, control_type, control_description, control_effectiveness,
            implementation_status, responsible_owner, target_completion_date,
            verification_method, created_by
        ) VALUES (
            :risk_id, :control_type, :control_description, :control_effectiveness,
            :implementation_status, :responsible_owner, :target_completion_date,
            :verification_method, :created_by
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':control_type' => $data['control_type'],
        ':control_description' => $data['control_description'],
        ':control_effectiveness' => $data['control_effectiveness'] ?? 'Medium',
        ':implementation_status' => $data['implementation_status'] ?? 'Planned',
        ':responsible_owner' => $data['responsible_owner'],
        ':target_completion_date' => $data['target_completion_date'] ?? null,
        ':verification_method' => $data['verification_method'] ?? null,
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['control_id' => $pdo->lastInsertId()]
    ];
}

function updateRiskControl($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['control_type', 'control_description', 'control_effectiveness',
                'implementation_status', 'responsible_owner', 'target_completion_date',
                'actual_completion_date', 'verification_method', 'verification_date',
                'verification_status'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_controls SET " . implode(', ', $fields) . " WHERE control_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

function deleteRiskControl($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM risk_controls WHERE control_id = :id");
    $stmt->execute([':id' => $id]);
    
    return ['success' => true];
}

// ============================================================================
// Regulatory Requirements Functions
// ============================================================================

function listRegulatoryRequirements($pdo, $filters = []) {
    $where = ['is_active = 1'];
    $params = [];
    
    if (!empty($filters['regulatory_body'])) {
        $where[] = 'regulatory_body = :regulatory_body';
        $params[':regulatory_body'] = $filters['regulatory_body'];
    }
    
    $sql = "SELECT * FROM regulatory_requirements WHERE " . implode(' AND ', $where) . " ORDER BY regulatory_body, regulation_reference";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function createRegulatoryRequirement($pdo, $data) {
    $required = ['regulatory_body', 'regulation_reference', 'requirement_description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO regulatory_requirements (
            regulatory_body, regulation_reference, requirement_description,
            applicability, effective_date, compliance_status, created_by
        ) VALUES (
            :regulatory_body, :regulation_reference, :requirement_description,
            :applicability, :effective_date, :compliance_status, :created_by
        )
    ");
    
    $stmt->execute([
        ':regulatory_body' => $data['regulatory_body'],
        ':regulation_reference' => $data['regulation_reference'],
        ':requirement_description' => $data['requirement_description'],
        ':applicability' => $data['applicability'] ?? 'All',
        ':effective_date' => $data['effective_date'] ?? null,
        ':compliance_status' => $data['compliance_status'] ?? 'Under Review',
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['requirement_id' => $pdo->lastInsertId()]
    ];
}

function updateRegulatoryRequirement($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['regulatory_body', 'regulation_reference', 'requirement_description',
                'applicability', 'effective_date', 'compliance_status', 'is_active'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE regulatory_requirements SET " . implode(', ', $fields) . " WHERE requirement_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

// ============================================================================
// Risk-Regulatory Mapping Functions
// ============================================================================

function listRiskRegulatoryMapping($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rrm.*,
            rr.regulatory_body,
            rr.regulation_reference,
            rr.requirement_description
        FROM risk_regulatory_mapping rrm
        JOIN regulatory_requirements rr ON rrm.requirement_id = rr.requirement_id
        WHERE rrm.risk_id = :risk_id
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function mapRiskToRequirement($pdo, $data) {
    $required = ['risk_id', 'requirement_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_regulatory_mapping (
            risk_id, requirement_id, compliance_gap,
            gap_description, remediation_plan, created_by
        ) VALUES (
            :risk_id, :requirement_id, :compliance_gap,
            :gap_description, :remediation_plan, :created_by
        )
        ON DUPLICATE KEY UPDATE
            compliance_gap = VALUES(compliance_gap),
            gap_description = VALUES(gap_description),
            remediation_plan = VALUES(remediation_plan)
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':requirement_id' => $data['requirement_id'],
        ':compliance_gap' => $data['compliance_gap'] ?? 'No',
        ':gap_description' => $data['gap_description'] ?? null,
        ':remediation_plan' => $data['remediation_plan'] ?? null,
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return ['success' => true];
}

// ============================================================================
// Risk Incidents Functions
// ============================================================================

function listRiskIncidents($pdo, $riskId = null) {
    $where = ['1=1'];
    $params = [];
    
    if ($riskId) {
        $where[] = 'ri.risk_id = :risk_id';
        $params[':risk_id'] = $riskId;
    }
    
    $sql = "
        SELECT 
            ri.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS reported_by_name
        FROM risk_incidents ri
        LEFT JOIN people p ON ri.reported_by = p.people_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ri.incident_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function createRiskIncident($pdo, $data) {
    $required = ['incident_date', 'incident_description', 'severity_actual', 'reported_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_incidents (
            risk_id, incident_date, incident_description, severity_actual,
            impact_description, root_cause, corrective_actions,
            preventive_actions, lessons_learned, reported_by
        ) VALUES (
            :risk_id, :incident_date, :incident_description, :severity_actual,
            :impact_description, :root_cause, :corrective_actions,
            :preventive_actions, :lessons_learned, :reported_by
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'] ?? null,
        ':incident_date' => $data['incident_date'],
        ':incident_description' => $data['incident_description'],
        ':severity_actual' => $data['severity_actual'],
        ':impact_description' => $data['impact_description'] ?? null,
        ':root_cause' => $data['root_cause'] ?? null,
        ':corrective_actions' => $data['corrective_actions'] ?? null,
        ':preventive_actions' => $data['preventive_actions'] ?? null,
        ':lessons_learned' => $data['lessons_learned'] ?? null,
        ':reported_by' => $data['reported_by']
    ]);
    
    return [
        'success' => true,
        'data' => ['incident_id' => $pdo->lastInsertId()]
    ];
}

// ============================================================================
// Risk Reviews Functions
// ============================================================================

function listRiskReviews($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rr.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS reviewer_name
        FROM risk_reviews rr
        LEFT JOIN people p ON rr.reviewer = p.people_id
        WHERE rr.risk_id = :risk_id
        ORDER BY rr.review_date DESC
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function createRiskReview($pdo, $data) {
    $required = ['risk_id', 'review_date', 'reviewer', 'risk_status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_reviews (
            risk_id, review_date, reviewer, risk_status,
            status_change_rationale, next_review_date,
            escalation_required, escalated_to, review_notes
        ) VALUES (
            :risk_id, :review_date, :reviewer, :risk_status,
            :status_change_rationale, :next_review_date,
            :escalation_required, :escalated_to, :review_notes
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':review_date' => $data['review_date'],
        ':reviewer' => $data['reviewer'],
        ':risk_status' => $data['risk_status'],
        ':status_change_rationale' => $data['status_change_rationale'] ?? null,
        ':next_review_date' => $data['next_review_date'] ?? null,
        ':escalation_required' => $data['escalation_required'] ?? 'No',
        ':escalated_to' => $data['escalated_to'] ?? null,
        ':review_notes' => $data['review_notes'] ?? null
    ]);
    
    // Update risk status in risk_register
    $updateStmt = $pdo->prepare("UPDATE risk_register SET status = :status WHERE risk_id = :risk_id");
    $updateStmt->execute([
        ':status' => $data['risk_status'],
        ':risk_id' => $data['risk_id']
    ]);
    
    return [
        'success' => true,
        'data' => ['review_id' => $pdo->lastInsertId()]
    ];
}

// ============================================================================
// Risk KPIs Functions
// ============================================================================

function listRiskKPIs($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rk.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS created_by_name
        FROM risk_kpis rk
        LEFT JOIN people p ON rk.created_by = p.people_id
        WHERE rk.risk_id = :risk_id
        ORDER BY rk.measurement_date DESC
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function createRiskKPI($pdo, $data) {
    $required = ['risk_id', 'kpi_name', 'measurement_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_kpis (
            risk_id, kpi_name, kpi_target, kpi_actual,
            measurement_period, trend, linked_iso_standard,
            measurement_date, created_by
        ) VALUES (
            :risk_id, :kpi_name, :kpi_target, :kpi_actual,
            :measurement_period, :trend, :linked_iso_standard,
            :measurement_date, :created_by
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':kpi_name' => $data['kpi_name'],
        ':kpi_target' => $data['kpi_target'] ?? null,
        ':kpi_actual' => $data['kpi_actual'] ?? null,
        ':measurement_period' => $data['measurement_period'] ?? 'Monthly',
        ':trend' => $data['trend'] ?? 'Not Measured',
        ':linked_iso_standard' => $data['linked_iso_standard'] ?? null,
        ':measurement_date' => $data['measurement_date'],
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['kpi_id' => $pdo->lastInsertId()]
    ];
}

function updateRiskKPI($pdo, $id, $data) {
    $fields = [];
    $params = [':id' => $id];
    
    $allowed = ['kpi_name', 'kpi_target', 'kpi_actual', 'measurement_period',
                'trend', 'linked_iso_standard', 'measurement_date'];
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_kpis SET " . implode(', ', $fields) . " WHERE kpi_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

// ============================================================================
// Process Map Integration Functions
// ============================================================================

function linkRiskToProcess($pdo, $data) {
    $required = ['risk_id', 'process_map_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO process_map_risk_register (process_map_id, risk_id, created_by)
        VALUES (:process_map_id, :risk_id, :created_by)
    ");
    
    $stmt->execute([
        ':process_map_id' => $data['process_map_id'],
        ':risk_id' => $data['risk_id'],
        ':created_by' => $data['created_by'] ?? null
    ]);
    
    return ['success' => true];
}

function unlinkRiskFromProcess($pdo, $data) {
    $required = ['risk_id', 'process_map_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        DELETE FROM process_map_risk_register
        WHERE process_map_id = :process_map_id AND risk_id = :risk_id
    ");
    
    $stmt->execute([
        ':process_map_id' => $data['process_map_id'],
        ':risk_id' => $data['risk_id']
    ]);
    
    return ['success' => true];
}

function getProcessRisks($pdo, $processId) {
    $stmt = $pdo->prepare("
        SELECT 
            r.risk_id,
            r.risk_code,
            r.risk_title,
            r.status,
            c.category_name,
            (SELECT MAX(ra.inherent_risk_score) 
             FROM risk_assessment ra 
             WHERE ra.risk_id = r.risk_id) AS current_risk_score
        FROM process_map_risk_register pmrr
        JOIN risk_register r ON pmrr.risk_id = r.risk_id
        LEFT JOIN risk_categories c ON r.category_id = c.category_id
        WHERE pmrr.process_map_id = :process_id
        ORDER BY r.risk_code
    ");
    $stmt->execute([':process_id' => $processId]);
    
    return [
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// ============================================================================
// Dashboard Statistics
// ============================================================================

function getDashboardStats($pdo) {
    $stats = [];
    
    // Total risks by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM risk_register
        GROUP BY status
    ");
    $stats['risks_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Risks by category
    $stmt = $pdo->query("
        SELECT c.category_name, COUNT(*) as count
        FROM risk_register r
        JOIN risk_categories c ON r.category_id = c.category_id
        GROUP BY c.category_id, c.category_name
        ORDER BY count DESC
    ");
    $stats['risks_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Risks by risk level
    $stmt = $pdo->query("
        SELECT 
            ra.inherent_risk_level,
            COUNT(*) as count
        FROM risk_assessment ra
        JOIN (
            SELECT risk_id, MAX(assessment_date) as max_date
            FROM risk_assessment
            GROUP BY risk_id
        ) latest ON ra.risk_id = latest.risk_id AND ra.assessment_date = latest.max_date
        GROUP BY ra.inherent_risk_level
    ");
    $stats['risks_by_level'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Controls by status
    $stmt = $pdo->query("
        SELECT implementation_status, COUNT(*) as count
        FROM risk_controls
        GROUP BY implementation_status
    ");
    $stats['controls_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Overdue reviews
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM risk_reviews
        WHERE next_review_date < CURDATE()
        AND risk_id IN (SELECT risk_id FROM risk_register WHERE status = 'Active')
    ");
    $stats['overdue_reviews'] = $stmt->fetchColumn();
    
    // Total counts
    $stmt = $pdo->query("SELECT COUNT(*) FROM risk_register");
    $stats['total_risks'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM risk_controls WHERE implementation_status != 'Implemented'");
    $stats['pending_controls'] = $stmt->fetchColumn();
    
    return [
        'success' => true,
        'data' => $stats
    ];
}


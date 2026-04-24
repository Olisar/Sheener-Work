<?php
/* File: sheener/php/api_risk_standards.php */

/**
 * Risk Standards Mapping API
 * Handles CRUD operations for risk-standards mappings
 */

session_start();
require_once 'database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    $rawInput = file_get_contents('php://input');
    $postInput = !empty($rawInput) ? json_decode($rawInput, true) : [];
    
    // Get parameters
    $mappingId = $_GET['id'] ?? $postInput['id'] ?? null;
    $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($mappingId) {
                // Get single mapping
                $response = getMapping($pdo, $mappingId);
            } elseif ($riskId) {
                // Get mappings for a risk
                $response = getRiskStandards($pdo, $riskId);
            } else {
                // Get all mappings with optional filters
                $filters = $_GET;
                $response = getAllMappings($pdo, $filters);
            }
            break;
            
        case 'POST':
            $response = createMapping($pdo, $postInput);
            break;
            
        case 'PUT':
            if (!$mappingId) {
                throw new Exception('Mapping ID required');
            }
            $response = updateMapping($pdo, $mappingId, $postInput);
            break;
            
        case 'DELETE':
            if (!$mappingId) {
                throw new Exception('Mapping ID required');
            }
            $response = deleteMapping($pdo, $mappingId);
            break;
            
        default:
            throw new Exception('Method not allowed');
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
// Mapping Functions
// ============================================================================

function getAllMappings($pdo, $filters = []) {
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['risk_id'])) {
        $where[] = 'rsm.risk_id = :risk_id';
        $params[':risk_id'] = $filters['risk_id'];
    }
    
    if (!empty($filters['standard_id'])) {
        $where[] = 'rsm.standard_id = :standard_id';
        $params[':standard_id'] = $filters['standard_id'];
    }
    
    if (!empty($filters['relevance_level'])) {
        $where[] = 'rsm.relevance_level = :relevance_level';
        $params[':relevance_level'] = $filters['relevance_level'];
    }
    
    if (!empty($filters['compliance_status'])) {
        $where[] = 'rsm.compliance_status = :compliance_status';
        $params[':compliance_status'] = $filters['compliance_status'];
    }
    
    $sql = "
        SELECT 
            rsm.*,
            r.risk_code,
            r.risk_title,
            rs.standard_name,
            rs.standard_code,
            rs.regulatory_body,
            rs.standard_type,
            CONCAT(p.FirstName, ' ', p.LastName) AS created_by_name
        FROM risk_standards_mapping rsm
        JOIN risk_register r ON rsm.risk_id = r.risk_id
        JOIN regulatory_standards rs ON rsm.standard_id = rs.standard_id
        LEFT JOIN people p ON rsm.created_by = p.people_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY rsm.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMapping($pdo, $mappingId) {
    $stmt = $pdo->prepare("
        SELECT 
            rsm.*,
            r.risk_code,
            r.risk_title,
            rs.standard_name,
            rs.standard_code,
            rs.regulatory_body,
            rs.standard_type,
            CONCAT(p.FirstName, ' ', p.LastName) AS created_by_name
        FROM risk_standards_mapping rsm
        JOIN risk_register r ON rsm.risk_id = r.risk_id
        JOIN regulatory_standards rs ON rsm.standard_id = rs.standard_id
        LEFT JOIN people p ON rsm.created_by = p.people_id
        WHERE rsm.mapping_id = :id
    ");
    $stmt->execute([':id' => $mappingId]);
    $mapping = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mapping) {
        throw new Exception('Mapping not found');
    }
    
    return $mapping;
}

function getRiskStandards($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rsm.*,
            rs.standard_name,
            rs.standard_code,
            rs.regulatory_body,
            rs.standard_type,
            rs.description AS standard_description,
            CONCAT(p.FirstName, ' ', p.LastName) AS created_by_name
        FROM risk_standards_mapping rsm
        JOIN regulatory_standards rs ON rsm.standard_id = rs.standard_id
        LEFT JOIN people p ON rsm.created_by = p.people_id
        WHERE rsm.risk_id = :risk_id
        ORDER BY 
            CASE rsm.relevance_level
                WHEN 'Primary' THEN 1
                WHEN 'Secondary' THEN 2
                WHEN 'Related' THEN 3
                WHEN 'Indirect' THEN 4
            END,
            rs.standard_name
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createMapping($pdo, $data) {
    $required = ['risk_id', 'standard_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
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
        ':risk_id' => $data['risk_id'],
        ':standard_id' => $data['standard_id'],
        ':relevance_level' => $data['relevance_level'] ?? 'Related',
        ':applicable_sections' => $data['applicable_sections'] ?? null,
        ':compliance_status' => $data['compliance_status'] ?? 'Under Review',
        ':notes' => $data['notes'] ?? null,
        ':created_by' => $data['created_by'] ?? $_SESSION['user_id'] ?? null
    ]);
    
    return [
        'success' => true,
        'data' => ['mapping_id' => $pdo->lastInsertId()]
    ];
}

function updateMapping($pdo, $mappingId, $data) {
    $fields = [];
    $params = [':id' => $mappingId];
    
    $allowed = ['relevance_level', 'applicable_sections', 'compliance_status', 'notes'];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_standards_mapping SET " . implode(', ', $fields) . " WHERE mapping_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return ['success' => true];
}

function deleteMapping($pdo, $mappingId) {
    $stmt = $pdo->prepare("DELETE FROM risk_standards_mapping WHERE mapping_id = :id");
    $stmt->execute([':id' => $mappingId]);
    
    return ['success' => true];
}


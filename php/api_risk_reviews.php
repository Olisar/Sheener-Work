<?php
/* File: sheener/php/api_risk_reviews.php */

/**
 * Risk Reviews API
 * Handles CRUD operations for risk reviews
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
    $reviewId = $_GET['id'] ?? $postInput['id'] ?? null;
    $riskId = $_GET['risk_id'] ?? $postInput['risk_id'] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($reviewId) {
                // Get single review
                $response = getReview($pdo, $reviewId);
            } elseif ($riskId) {
                // Get reviews for a risk
                $response = getRiskReviews($pdo, $riskId);
            } else {
                // Get all reviews with optional filters
                $filters = $_GET;
                $response = getAllReviews($pdo, $filters);
            }
            break;
            
        case 'POST':
            $response = createReview($pdo, $postInput);
            break;
            
        case 'PUT':
            if (!$reviewId) {
                throw new Exception('Review ID required');
            }
            $response = updateReview($pdo, $reviewId, $postInput);
            break;
            
        case 'DELETE':
            if (!$reviewId) {
                throw new Exception('Review ID required');
            }
            $response = deleteReview($pdo, $reviewId);
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
// Review Functions
// ============================================================================

function getAllReviews($pdo, $filters = []) {
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where[] = 'rr.risk_status = :status';
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['review_type'])) {
        $where[] = 'rr.review_type = :review_type';
        $params[':review_type'] = $filters['review_type'];
    }
    
    $sql = "
        SELECT 
            rr.*,
            r.risk_code,
            r.risk_title,
            CONCAT(p.FirstName, ' ', p.LastName) AS reviewer_name,
            CONCAT(ap.FirstName, ' ', ap.LastName) AS approved_by_name
        FROM risk_reviews rr
        JOIN risk_register r ON rr.risk_id = r.risk_id
        LEFT JOIN people p ON rr.reviewer = p.people_id
        LEFT JOIN people ap ON rr.review_approved_by = ap.people_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY rr.review_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getReview($pdo, $reviewId) {
    $stmt = $pdo->prepare("
        SELECT 
            rr.*,
            r.risk_code,
            r.risk_title,
            CONCAT(p.FirstName, ' ', p.LastName) AS reviewer_name,
            CONCAT(ap.FirstName, ' ', ap.LastName) AS approved_by_name
        FROM risk_reviews rr
        JOIN risk_register r ON rr.risk_id = r.risk_id
        LEFT JOIN people p ON rr.reviewer = p.people_id
        LEFT JOIN people ap ON rr.review_approved_by = ap.people_id
        WHERE rr.review_id = :id
    ");
    $stmt->execute([':id' => $reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        throw new Exception('Review not found');
    }
    
    return $review;
}

function getRiskReviews($pdo, $riskId) {
    $stmt = $pdo->prepare("
        SELECT 
            rr.*,
            CONCAT(p.FirstName, ' ', p.LastName) AS reviewer_name,
            CONCAT(ap.FirstName, ' ', ap.LastName) AS approved_by_name
        FROM risk_reviews rr
        LEFT JOIN people p ON rr.reviewer = p.people_id
        LEFT JOIN people ap ON rr.review_approved_by = ap.people_id
        WHERE rr.risk_id = :risk_id
        ORDER BY rr.review_date DESC
    ");
    $stmt->execute([':risk_id' => $riskId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createReview($pdo, $data) {
    $required = ['risk_id', 'review_date', 'reviewer', 'risk_status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field required: $field");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO risk_reviews (
            risk_id, review_date, review_type, review_outcome,
            reviewer, risk_status, status_change_rationale,
            next_review_date, escalation_required, escalated_to,
            review_notes, action_items, review_approved_by,
            review_approval_date
        ) VALUES (
            :risk_id, :review_date, :review_type, :review_outcome,
            :reviewer, :risk_status, :status_change_rationale,
            :next_review_date, :escalation_required, :escalated_to,
            :review_notes, :action_items, :review_approved_by,
            :review_approval_date
        )
    ");
    
    $stmt->execute([
        ':risk_id' => $data['risk_id'],
        ':review_date' => $data['review_date'],
        ':review_type' => $data['review_type'] ?? 'Scheduled',
        ':review_outcome' => $data['review_outcome'] ?? null,
        ':reviewer' => $data['reviewer'],
        ':risk_status' => $data['risk_status'],
        ':status_change_rationale' => $data['status_change_rationale'] ?? null,
        ':next_review_date' => $data['next_review_date'] ?? null,
        ':escalation_required' => $data['escalation_required'] ?? 'No',
        ':escalated_to' => $data['escalated_to'] ?? null,
        ':review_notes' => $data['review_notes'] ?? null,
        ':action_items' => $data['action_items'] ?? null,
        ':review_approved_by' => $data['review_approved_by'] ?? null,
        ':review_approval_date' => $data['review_approval_date'] ?? null
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

function updateReview($pdo, $reviewId, $data) {
    $fields = [];
    $params = [':id' => $reviewId];
    
    $allowed = ['review_date', 'review_type', 'review_outcome', 'reviewer',
                'risk_status', 'status_change_rationale', 'next_review_date',
                'escalation_required', 'escalated_to', 'review_notes',
                'action_items', 'review_approved_by', 'review_approval_date'];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE risk_reviews SET " . implode(', ', $fields) . " WHERE review_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Update risk status if changed
    if (isset($data['risk_status'])) {
        $stmt = $pdo->prepare("SELECT risk_id FROM risk_reviews WHERE review_id = :id");
        $stmt->execute([':id' => $reviewId]);
        $riskId = $stmt->fetchColumn();
        
        if ($riskId) {
            $updateStmt = $pdo->prepare("UPDATE risk_register SET status = :status WHERE risk_id = :risk_id");
            $updateStmt->execute([
                ':status' => $data['risk_status'],
                ':risk_id' => $riskId
            ]);
        }
    }
    
    return ['success' => true];
}

function deleteReview($pdo, $reviewId) {
    $stmt = $pdo->prepare("DELETE FROM risk_reviews WHERE review_id = :id");
    $stmt->execute([':id' => $reviewId]);
    
    return ['success' => true];
}


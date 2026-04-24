<?php
/* File: sheener/api/rca/fishbone_categories.php */

/**
 * Fishbone Categories API
 * Handles CRUD operations for Fishbone categories
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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = [];
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $category_id = $_GET['category_id'] ?? $input['category_id'] ?? null;
    $rca_id = $_GET['rca_id'] ?? $input['rca_id'] ?? null;
    
    if ($method === 'POST') {
        // Create new category
        $category_name = $input['category_name'] ?? $input['name'] ?? null;
        if (!$rca_id || !$category_name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'rca_id and category name are required']);
            exit;
        }
        
        // Check if category already exists
        $checkSql = "SELECT category_id FROM rca_fishbone_categories 
                     WHERE rca_id = :rca_id AND name = :category_name";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':rca_id' => $rca_id,
            ':category_name' => $category_name
        ]);
        
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category already exists']);
            exit;
        }
        
        // Determine next sort order
        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order 
                                    FROM rca_fishbone_categories 
                                    WHERE rca_id = :rca_id");
        $orderStmt->execute([':rca_id' => $rca_id]);
        $nextOrder = $orderStmt->fetchColumn() ?: 1;
        
        $sql = "INSERT INTO rca_fishbone_categories 
                (rca_id, name, sort_order)
                VALUES (:rca_id, :category_name, :sort_order)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rca_id' => $rca_id,
            ':category_name' => $category_name,
            ':sort_order' => $nextOrder
        ]);
        
        $newCategoryId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'category_id' => $newCategoryId,
            'message' => 'Category created successfully'
        ]);
    } elseif ($method === 'DELETE' && $category_id) {
        // Delete category (will cascade delete causes)
        $sql = "DELETE FROM rca_fishbone_categories WHERE category_id = :category_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category_id' => $category_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

?>


<?php
/* File: sheener/php/api_risk_lookup.php */

/**
 * Risk Assessment Lookup API
 * Handles lookup endpoints: categories, people, standards, subcategories
 */

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
    
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? null;
    
    switch ($type) {
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
                exit;
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
                       COALESCE(CONCAT(FirstName, ' ', LastName), Email, CONCAT('Person ', people_id)) as name,
                       FirstName as first_name, 
                       LastName as last_name, 
                       Email as email
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
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid lookup type. Use: categories, subcategories, people, or standards']);
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


<?php
/* File: sheener/php/get_areas.php */

// file: php/get_areas.php
// Returns list of areas from the Areas table for autocomplete

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once 'database.php';

ob_clean();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch all active areas from the Areas table
    $query = "
        SELECT 
            area_id,
            area_name,
            area_type,
            description,
            location_code,
            is_active
        FROM areas
        WHERE is_active = 1
        ORDER BY area_name ASC
    ";

    $stmt = $pdo->query($query);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $areas]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>


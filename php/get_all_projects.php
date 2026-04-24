<?php
/* File: sheener/php/get_all_projects.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Suppress HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once 'database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    $response = ['success' => false, 'data' => []];
    
    // Check if projects table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'projects'")->fetch();
    
    if ($tableCheck) {
        $sql = "SELECT project_id, project_name FROM projects ORDER BY project_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['data'] = $projects;
        $response['success'] = true;
    } else {
        // Table doesn't exist, return empty array
        $response['data'] = [];
        $response['success'] = true;
        $response['message'] = 'Projects table not found';
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    // Return JSON error instead of HTML
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'data' => [] // Return empty array on error so the form can still work
    ]);
}
?>

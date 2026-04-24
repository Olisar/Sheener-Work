<?php
/* File: sheener/php/get_processes.php */

error_reporting(0);
ini_set('display_errors', 0);
ob_start(); // Start output buffering

// file name Sheener/php/get_processes.php
require_once 'database.php';

ob_clean(); // Clear any output before setting headers
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

    // Get all active processes from process_map
    $query = "
        SELECT 
            id as process_id,
            id,
            text as process_name,
            text,
            type,
            description,
            status,
            parent
        FROM process_map
        WHERE status = 'Active' OR status IS NULL
        ORDER BY text, id
    ";

    $stmt = $pdo->query($query);
    $processes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $processes]);
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

<?php
/* File: sheener/php/get_glossary.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once 'database.php'; // Ensure database connection is included

try {
    // Create a new Database instance
    $db = new Database();
    $pdo = $db->getConnection(); // Get the PDO connection

    // Prepare SQL query to fetch glossary terms
    $stmt = $pdo->prepare("SELECT id, term, definition, category, source, created_at, updated_at FROM glossary");
    $stmt->execute(); // Execute query

    // Fetch results
    $glossary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return response in JSON format
    echo json_encode(["success" => true, "data" => $glossary]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>

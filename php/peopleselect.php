<?php
/* File: sheener/php/peopleselect.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT people_id, FirstName, LastName FROM people";
    $stmt = $pdo->query($query);
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($people);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>

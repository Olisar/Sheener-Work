<?php
/* File: sheener/php/get_person.php */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'database.php';

$people_id = $_GET['people_id'] ?? null;

if (!$people_id) {
    echo json_encode(['success' => false, 'error' => 'No people_id provided']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT * FROM people WHERE people_id = :people_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':people_id' => $people_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($person) {
        echo json_encode(['success' => true, 'person' => $person]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Person not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

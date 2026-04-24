<?php
/* File: sheener/php/delete_assessment.php */

header('Content-Type: application/json');

require_once 'database.php';

// Support both GET and DELETE methods
$assessment_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['assessment_id'])) {
    $assessment_id = intval($_GET['assessment_id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Parse DELETE request body if needed, or use query string
    parse_str(file_get_contents('php://input'), $delete_vars);
    $assessment_id = isset($delete_vars['assessment_id']) ? intval($delete_vars['assessment_id']) : 
                     (isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : null);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assessment_id'])) {
    $assessment_id = intval($_POST['assessment_id']);
}

if (!$assessment_id) {
    echo json_encode(["success" => false, "error" => "Missing assessment_id parameter"]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "DELETE FROM assessments WHERE assessment_id = :assessment_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':assessment_id' => $assessment_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "No assessment found with the specified ID"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>

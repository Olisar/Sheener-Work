

<?php
/* File: sheener/php/check_conflicts.php */

// file name Sheener/php/check_conflicts.php
// Include the new database.php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $task_id = $_POST['task_id'];
    $assessment_date = $_POST['assessment_date'];
    $assessor_name = $_POST['assessor_name'];
    $comments = $_POST['comments'];

    try {
        // Create a new Database instance and get the PDO connection
        $database = new Database();
        $pdo = $database->getConnection();

        // Check if assessment_id is provided (for update)
        if (isset($_POST['assessment_id']) && !empty($_POST['assessment_id'])) {
            $assessment_id = $_POST['assessment_id'];
            $query = "UPDATE assessments SET task_id = :task_id, assessment_date = :assessment_date, assessor_name = :assessor_name, comments = :comments WHERE assessment_id = :assessment_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':task_id' => $task_id,
                ':assessment_date' => $assessment_date,
                ':assessor_name' => $assessor_name,
                ':comments' => $comments,
                ':assessment_id' => $assessment_id
            ]);
        } else {
            // Insert new record
            $query = "INSERT INTO assessments (task_id, assessment_date, assessor_name, comments) VALUES (:task_id, :assessment_date, :assessor_name, :comments)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':task_id' => $task_id,
                ':assessment_date' => $assessment_date,
                ':assessor_name' => $assessor_name,
                ':comments' => $comments
            ]);
        }

        // Check if the query was successful
        if ($stmt->rowCount() > 0) {
            echo "Success";
        } else {
            echo "Error: No rows affected.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
    }
}
?>

<?php
require_once 'database.php';
header('Content-Type: application/json');

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT cp.conflicting_permit_id, p.permit_type 
        FROM conflicting_permits cp
        JOIN permits p ON cp.conflicting_permit_id = p.permit_id
        WHERE cp.permit_id = ? AND cp.resolved = 0
    ");
    $stmt->execute([$_POST['permit_id']]);
    
    echo json_encode(['success' => true, 'conflicts' => $stmt->fetchAll()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

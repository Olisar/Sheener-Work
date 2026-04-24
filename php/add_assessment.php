

<?php
/* File: sheener/php/add_assessment.php */

// file name Sheener/php/add_assessment.php
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

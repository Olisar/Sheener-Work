<?php
/* File: sheener/php/delete_person.php */

header('Content-Type: application/json');

require_once 'database.php';

if (isset($_GET['people_id'])) {
    $people_id = intval($_GET['people_id']);

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Step 1: Delete related records from `personalinformation`
        $query1 = "DELETE FROM personalinformation WHERE PersonID = :people_id";
        $stmt1 = $pdo->prepare($query1);
        $stmt1->execute([':people_id' => $people_id]);

        // Step 2: Delete related records from `people_departments`
        $query2 = "DELETE FROM people_departments WHERE PersonID = :people_id";
        $stmt2 = $pdo->prepare($query2);
        $stmt2->execute([':people_id' => $people_id]);

        // Step 3: Delete related records from `tasks`
        $query3 = "DELETE FROM tasks WHERE assigned_to = :people_id";
        $stmt3 = $pdo->prepare($query3);
        $stmt3->execute([':people_id' => $people_id]);

        // Step 4: Delete the main record from `people`
        $query4 = "DELETE FROM people WHERE people_id = :people_id";
        $stmt4 = $pdo->prepare($query4);
        $stmt4->execute([':people_id' => $people_id]);

        if ($stmt4->rowCount() > 0) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No record found with the specified ID"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Missing 'people_id' parameter"]);
}
?>

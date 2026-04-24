<?php
/* File: sheener/php/update_person.php */

require 'database.php'; // Include the Database class

header('Content-Type: application/json');

try {
    // Create an instance of the Database class and get the PDO connection
    $db = new Database();
    $pdo = $db->getConnection();
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database connection error: " . $e->getMessage()]);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both JSON and form data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Fallback to POST form data
        $data = $_POST;
    }
    
    // Retrieve and sanitize inputs
    $people_id = isset($data['people_id']) ? intval($data['people_id']) : null;
    $firstName = trim($data['FirstName'] ?? '');
    $lastName = trim($data['LastName'] ?? '');
    $dateOfBirth = trim($data['DateOfBirth'] ?? '');
    $email = trim($data['Email'] ?? '');
    $phoneNumber = trim($data['PhoneNumber'] ?? '');
    $position = trim($data['Position'] ?? '');
    $isActive = isset($data['IsActive']) ? intval($data['IsActive']) : 1;

    // Ensure mandatory fields are not empty
    if (!$people_id || empty($firstName) || empty($lastName) || empty($email)) {
        echo json_encode(["success" => false, "error" => "Missing required fields."]);
        exit;
    }

    try {
        // Prepare the SQL statement using PDO
        $stmt = $pdo->prepare("UPDATE people 
            SET FirstName = ?, LastName = ?, DateOfBirth = ?, Email = ?, PhoneNumber = ?, Position = ?, IsActive = ?
            WHERE people_id = ?");

        // Execute with sanitized values
        $stmt->execute([$firstName, $lastName, $dateOfBirth ?: null, $email, $phoneNumber ?: null, $position ?: null, $isActive, $people_id]);

        // Check if any row was affected
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Person updated successfully."]);
        } else {
            echo json_encode(["success" => false, "error" => "No changes made."]);
        }

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => "Execute failed: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
}
?>

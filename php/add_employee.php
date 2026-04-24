<?php
/* File: sheener/php/add_employee.php */

header('Content-Type: application/json');

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $dateOfBirth = $_POST["dateOfBirth"];
    $email = $_POST["email"];
    $phoneNumber = $_POST["phoneNumber"];

    $query = "INSERT INTO people (FirstName, LastName, DateOfBirth, Email, PhoneNumber) VALUES (:firstName, :lastName, :dateOfBirth, :email, :phoneNumber)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':dateOfBirth' => $dateOfBirth,
        ':email' => $email,
        ':phoneNumber' => $phoneNumber
    ]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>

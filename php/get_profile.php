<?php
/* File: sheener/php/get_profile.php */
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch user profile from people and personalinformation
    $query = "
        SELECT 
            p.people_id,
            p.FirstName AS first_name,
            p.LastName AS last_name,
            p.Email AS email,
            p.PhoneNumber AS phone,
            p.Position AS position,
            p.DateOfBirth AS dob,
            pi.Username AS username
        FROM people p
        LEFT JOIN personalinformation pi ON p.people_id = pi.PersonID
        WHERE p.people_id = :user_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>

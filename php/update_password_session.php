<?php
/* File: sheener/php/update_password_session.php */
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'error' => 'All password fields are required.']);
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'New password and confirmation do not match.']);
        exit();
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters long.']);
        exit();
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // 1. Verify current password
        $query = "SELECT PasswordHash FROM personalinformation WHERE PersonID = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['PasswordHash'])) {
            echo json_encode(['success' => false, 'error' => 'Incorrect current password.']);
            exit();
        }

        // 2. Hash new password
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // 3. Update password
        $update_query = "UPDATE personalinformation SET PasswordHash = :new_hash WHERE PersonID = :user_id";
        $update_stmt = $pdo->prepare($update_query);
        $result = $update_stmt->execute([
            ':new_hash' => $new_hash,
            ':user_id' => $user_id
        ]);

        if ($result) {
            // Log audit
            $database->logAudit($pdo, 'CHANGE_PASSWORD', 'personalinformation', $user_id, 'User changed their own password');

            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update password.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>

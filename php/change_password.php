<?php
/* File: sheener/php/change_password.php */

// file: php/change_password.php
// Handles password change functionality for forgot password feature

header('Content-Type: application/json');
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        if ($action === 'verify') {
            // Step 1: Verify old password
            $username = $_POST['username'] ?? '';
            $oldPassword = $_POST['old_password'] ?? '';

            if (empty($username) || empty($oldPassword)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username and current password are required.'
                ]);
                exit();
            }

            // Fetch user details from the `personalinformation` table
            $query = "SELECT PersonalInfoID, PersonID, PasswordHash FROM personalinformation WHERE Username = :username";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($oldPassword, $user['PasswordHash'])) {
                // Password is valid
                echo json_encode([
                    'success' => true,
                    'message' => 'Password verified successfully.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid username or password. Please check your credentials and try again.'
                ]);
            }
        } elseif ($action === 'change') {
            // Step 2: Change password
            $username = $_POST['username'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            if (empty($username) || empty($newPassword)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Username and new password are required.'
                ]);
                exit();
            }

            // Check if user exists
            $query = "SELECT PersonalInfoID, PasswordHash FROM personalinformation WHERE Username = :username";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found. Please check your username.'
                ]);
                exit();
            }

            // Hash the new password using the same method as login (PASSWORD_DEFAULT)
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password in database
            $updateQuery = "UPDATE personalinformation SET PasswordHash = :passwordHash WHERE Username = :username";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':passwordHash' => $newPasswordHash,
                ':username' => $username
            ]);

            if ($updateStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update password. Please try again.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action specified.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred. Please contact the administrator.'
        ]);
        error_log("Password change error: " . $e->getMessage());
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method.'
    ]);
}
?>


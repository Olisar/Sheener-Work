<?php
/* File: sheener/php/update_profile.php */
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $dob = trim($_POST['dob'] ?? '');

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username)) {
        echo json_encode(['success' => false, 'error' => 'First Name, Last Name, Username, and Email are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
        exit();
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // 1. Check if username is already taken by another user
        $check_query = "SELECT PersonID FROM personalinformation WHERE Username = :username AND PersonID != :user_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([':username' => $username, ':user_id' => $user_id]);
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username is already taken.']);
            exit();
        }

        // 2. Start transaction
        $pdo->beginTransaction();

        // 3. Update people table
        $query_people = "
            UPDATE people 
            SET FirstName = :first_name, 
                LastName = :last_name, 
                Email = :email, 
                PhoneNumber = :phone, 
                Position = :position, 
                DateOfBirth = :dob
            WHERE people_id = :user_id
        ";
        
        $stmt_people = $pdo->prepare($query_people);
        $stmt_people->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':position' => $position,
            ':dob' => !empty($dob) ? $dob : null,
            ':user_id' => $user_id
        ]);

        // 4. Update personalinformation table
        $query_pi = "
            UPDATE personalinformation 
            SET Username = :username 
            WHERE PersonID = :user_id
        ";
        $stmt_pi = $pdo->prepare($query_pi);
        $stmt_pi->execute([
            ':username' => $username,
            ':user_id' => $user_id
        ]);

        $pdo->commit();

        // Update session data
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        
        // Log audit
        $database->logAudit($pdo, 'UPDATE_PROFILE', 'people/personalinformation', $user_id, 'User updated their own profile including username');

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>

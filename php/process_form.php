<?php
/* File: sheener/php/process_form.php */

session_start();
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Fetch user details from the `people` table
        $query = "SELECT people_id, FirstName, LastName, PasswordHash FROM people WHERE Email = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['user_id'] = $user['people_id'];
            $_SESSION['first_name'] = $user['FirstName'];
            $_SESSION['last_name'] = $user['LastName'];

            // Fetch the user's role
            $query = "SELECT RoleID FROM people_roles WHERE PersonID = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':user_id' => $user['people_id']]);
            $role = $stmt->fetchColumn();
            $_SESSION['role_id'] = $role;

            // Fetch the user's department
            $query = "SELECT DepartmentID FROM people_departments WHERE PersonID = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':user_id' => $user['people_id']]);
            $department = $stmt->fetchColumn();
            $_SESSION['department_id'] = $department;

            // Redirect based on the user's role
            switch ($role) {
                case 1: // Admin
                    header("Location: ../dashboard_admin.php");
                    break;
                case 2: // Manager
                    header("Location: ../dashboard_manager.php");
                    break;
                case 3: // Employee
                    header("Location: ../dashboard_employee.php");
                    break;
                default:
                    echo "<script>alert('Role not recognized.'); window.location.href='../index.php';</script>";
                    exit();
            }
            exit();
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href='../index.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database error: " . $e->getMessage() . "'); window.location.href='../index.php';</script>";
    }
} else {
    echo "<script>alert('Invalid request method.'); window.location.href='../index.php';</script>";
}
?>

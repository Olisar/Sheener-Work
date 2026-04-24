<?php
/* File: sheener/php/get_roles.php */

session_start();
header('Content-Type: application/json');
require_once 'database.php';
require_once 'rbac_helper.php';

// Only Admin and Super Admin can access
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$role_ids = $_SESSION['role_ids'] ?? [];
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $query = "SELECT RoleID FROM roles WHERE RoleName IN ('Admin', 'Super Admin')";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $adminRoleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasAccess = !empty(array_intersect($role_ids, $adminRoleIds));
    if (!$hasAccess) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Access check failed']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $query = "SELECT RoleID, RoleName, Description FROM roles ORDER BY RoleName";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $roles
    ]);
} catch (PDOException $e) {
    error_log("Get Roles Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load roles'
    ]);
}
?>


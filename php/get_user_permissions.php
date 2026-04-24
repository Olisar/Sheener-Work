<?php
/* File: sheener/php/get_user_permissions.php */

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

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get all permissions for the user through their roles
    $query = "SELECT DISTINCT 
                p.PermissionID,
                p.PermissionName,
                r.RoleID,
                r.RoleName
              FROM people_roles pr
              JOIN rolepermissions rp ON pr.RoleID = rp.RoleID
              JOIN permissions p ON rp.PermissionID = p.PermissionID
              JOIN roles r ON pr.RoleID = r.RoleID
              WHERE pr.PersonID = :user_id
              ORDER BY p.PermissionName, r.RoleName";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $permissions
    ]);
} catch (PDOException $e) {
    error_log("Get User Permissions Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load user permissions'
    ]);
}
?>


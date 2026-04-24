<?php
/* File: sheener/php/toggle_role_permission.php */

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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$roleId = $input['role_id'] ?? null;
$permissionId = $input['permission_id'] ?? null;
$enabled = $input['enabled'] ?? false;

if (!$roleId || !$permissionId) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $pdo->beginTransaction();

    if ($enabled) {
        // Add permission to role
        $query = "INSERT INTO rolepermissions (RoleID, PermissionID) 
                  VALUES (:role_id, :permission_id)
                  ON DUPLICATE KEY UPDATE RoleID = RoleID";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
        $message = 'Permission granted to role';
    } else {
        // Remove permission from role
        $query = "DELETE FROM rolepermissions 
                  WHERE RoleID = :role_id AND PermissionID = :permission_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
        $message = 'Permission removed from role';
    }

    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Toggle Role Permission Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update permission: ' . $e->getMessage()
    ]);
}
?>


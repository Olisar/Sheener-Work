<?php
/* File: sheener/php/rbac_helper.php */

/**
 * RBAC Helper Functions
 * Provides standardized functions for role-based access control
 * 
 * Usage: require_once 'php/rbac_helper.php';
 */

// Ensure Database class is available
if (!class_exists('Database')) {
    require_once __DIR__ . '/database.php';
}

/**
 * Check if user has a specific role
 * 
 * @param int|string $roleIdOrName Role ID or Role Name to check
 * @param array|null $userRoles Optional: array of user's role IDs (defaults to session)
 * @return bool True if user has the role
 */
function userHasRole($roleIdOrName, $userRoles = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Get user roles from session if not provided
    if ($userRoles === null) {
        $userRoles = $_SESSION['role_ids'] ?? [];
    }

    // If checking by role name, convert to role ID
    if (is_string($roleIdOrName)) {
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            $query = "SELECT RoleID FROM roles WHERE RoleName = :role_name";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':role_name' => $roleIdOrName]);
            $roleIdOrName = $stmt->fetchColumn();
            if (!$roleIdOrName) {
                return false;
            }
        } catch (PDOException $e) {
            error_log("RBAC Error: " . $e->getMessage());
            return false;
        }
    }

    return in_array($roleIdOrName, $userRoles);
}

/**
 * Check if user has a specific permission
 * 
 * @param string $permissionName Permission name to check
 * @param array|null $userRoles Optional: array of user's role IDs (defaults to session)
 * @return bool True if user has the permission
 */
function userHasPermission($permissionName, $userRoles = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Get user roles from session if not provided
    if ($userRoles === null) {
        $userRoles = $_SESSION['role_ids'] ?? [];
    }

    if (empty($userRoles)) {
        return false;
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Get permission ID
        $query = "SELECT PermissionID FROM permissions WHERE PermissionName = :permission_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':permission_name' => $permissionName]);
        $permissionId = $stmt->fetchColumn();
        
        if (!$permissionId) {
            return false;
        }

        // Check if any of user's roles have this permission
        $placeholders = str_repeat('?,', count($userRoles) - 1) . '?';
        $query = "SELECT COUNT(*) FROM rolepermissions 
                 WHERE RoleID IN ($placeholders) AND PermissionID = ?";
        $params = array_merge($userRoles, [$permissionId]);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("RBAC Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all roles for the current user
 * 
 * @param int|null $personId Optional: Person ID (defaults to session user_id)
 * @return array Array of role information (RoleID, RoleName)
 */
function getUserRoles($personId = null) {
    if ($personId === null) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        $personId = $_SESSION['user_id'];
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "SELECT r.RoleID, r.RoleName, r.Description 
                 FROM people_roles pr 
                 JOIN roles r ON pr.RoleID = r.RoleID 
                 WHERE pr.PersonID = :person_id 
                 ORDER BY r.RoleName";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':person_id' => $personId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all permissions for the current user (across all their roles)
 * 
 * @param int|null $personId Optional: Person ID (defaults to session user_id)
 * @return array Array of permission information (PermissionID, PermissionName)
 */
function getUserPermissions($personId = null) {
    if ($personId === null) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }
        $personId = $_SESSION['user_id'];
    }

    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $query = "SELECT DISTINCT p.PermissionID, p.PermissionName 
                 FROM people_roles pr 
                 JOIN rolepermissions rp ON pr.RoleID = rp.RoleID 
                 JOIN permissions p ON rp.PermissionID = p.PermissionID 
                 WHERE pr.PersonID = :person_id 
                 ORDER BY p.PermissionName";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':person_id' => $personId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("RBAC Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Require a specific role - redirects if user doesn't have it
 * 
 * @param int|string $roleIdOrName Role ID or Role Name required
 * @param string $redirectUrl URL to redirect to if access denied
 */
function requireRole($roleIdOrName, $redirectUrl = '../index.php') {
    if (!userHasRole($roleIdOrName)) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Require a specific permission - redirects if user doesn't have it
 * 
 * @param string $permissionName Permission name required
 * @param string $redirectUrl URL to redirect to if access denied
 */
function requirePermission($permissionName, $redirectUrl = '../index.php') {
    if (!userHasPermission($permissionName)) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Check if user is admin (convenience function)
 * 
 * @return bool True if user has Admin role
 */
function isAdmin() {
    return userHasRole('Admin');
}

/**
 * Check if user has Permit role (role-based, not person-based)
 * 
 * @return bool True if user has Permit role
 */
function isPermitUser() {
    return userHasRole('Permit');
}

/**
 * Check if user meets attribute-based conditions
 * Supports department, company, IsActive, site, etc.
 * 
 * @param array $attributes Array of attribute conditions:
 *   - 'department' => string|array: Department name(s) to match
 *   - 'company' => string|array: Company name(s) to match
 *   - 'IsActive' => bool: Active status requirement
 *   - 'site' => string|array: Site name(s) to match
 * @param int|null $personId Optional: Person ID (defaults to session user_id)
 * @return bool True if user meets all attribute conditions
 */
function userMeetsAttributes($attributes, $personId = null) {
    if (empty($attributes)) {
        return true; // No attribute requirements = always pass
    }
    
    if ($personId === null) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        $personId = $_SESSION['user_id'];
    }
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Build query to get user attributes
        $query = "SELECT 
                    p.IsActive,
                    COALESCE(d.DepartmentName, '') AS DepartmentName,
                    COALESCE(c.CompanyName, '') AS CompanyName
                  FROM people p
                  LEFT JOIN people_departments pd ON p.people_id = pd.PersonID
                  LEFT JOIN departments d ON pd.DepartmentID = d.department_id
                  LEFT JOIN vendor c ON p.company_id = c.company_id
                  WHERE p.people_id = :person_id
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':person_id' => $personId]);
        $userAttrs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userAttrs) {
            return false;
        }
        
        // Check each attribute requirement
        foreach ($attributes as $attrName => $attrValue) {
            $userValue = $userAttrs[$attrName] ?? null;
            
            if (is_array($attrValue)) {
                // Multiple allowed values
                if (!in_array($userValue, $attrValue)) {
                    return false;
                }
            } else {
                // Single required value
                if ($userValue != $attrValue) {
                    return false;
                }
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("RBAC Attribute Check Error: " . $e->getMessage());
        return false;
    }
}


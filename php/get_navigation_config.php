<?php
/* File: sheener/php/get_navigation_config.php */

/**
 * Navigation Configuration API
 * Returns navigation items and topbar functions based on user roles
 * 
 * This is the single source of truth for role-based navigation
 */

session_start();
require_once 'database.php';
require_once 'rbac_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get user roles and permissions
    $userRoles = getUserRoles();
    $userPermissions = getUserPermissions();
    $roleNames = array_column($userRoles, 'RoleName');
    $roleIds = array_column($userRoles, 'RoleID');
    $permissionNames = array_column($userPermissions, 'PermissionName');

    // Get user attributes for attribute-based access control
    $userAttributes = [];

    try {
        $query = "SELECT 
p.IsActive,
        COALESCE(d.DepartmentName, '') AS DepartmentName,
        COALESCE(c.CompanyName, '') AS CompanyName FROM people p LEFT JOIN people_departments pd ON p.people_id=pd.PersonID LEFT JOIN departments d ON pd.DepartmentID=d.department_id LEFT JOIN vendor c ON p.company_id=c.company_id WHERE p.people_id=: person_id LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':person_id' => $_SESSION['user_id']]);
        $userAttributes = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching user attributes: " . $e->getMessage());
    }

    // Define navigation items with role/permission/attribute requirements
    // Format: [
    //   'page' => 'page.php',
    //   'label' => 'Display Name',
    //   'roles' => ['Admin', 'User'],           // Required: array of role names (OR logic)
    //   'permission' => 'optional_permission',  // Optional: specific permission required
    //   'attributes' => [                       // Optional: attribute-based conditions
    //     'department' => 'EHS',                // Single value or array for multiple
    //     'IsActive' => 1
    //   ],
    //   'category' => 'Main'
    // ]
    $allNavItems = [ // Permit Management (only for Permit role)
        [
            'page' => 'permit_list.php',
            'label' => 'Permit List',
            'roles' => ['Permit'],
            'permission' => null,
            'attributes' => null,
            'category' => 'Permit Management'
        ],
        [
            'page' => 'permit_list.php#openAddPermitModal',
            'label' => 'Create New Permit',
            'roles' => ['Permit'],
            'permission' => null,
            'attributes' => ['IsActive' => 1],
            // Only active users can create permits
            'category' => 'Permit Management'
        ],

        // Main Navigation (available to most users, except Permit-only users)
        [
            'page' => 'dashboard.php',
            'label' => 'Dashboard',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],

        [
            'page' => 'event_center.php',
            'label' => 'Event Center',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],










        [
            'page' => '7ps_registry.php',
            'label' => '7Ps',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'process_analytics.html',
            'label' => 'Analytics',
            'roles' => [
                'Admin',
                'Supervisor'
            ],
            'permission' => 'view_analytics',
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'permit_list.php',
            'label' => 'PTW Center',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'trainingIndex.html',
            'label' => 'Training',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'CC_List.php',
            'label' => 'Change Control',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'vendor_list.php',
            'label' => 'Vendor List',
            'roles' => [
                'Admin',
                'Approver',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'batch.php',
            'label' => 'Batches',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'glossary.php',
            'label' => 'Glossary',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],








        [
            'page' => 'HSTopics/SafetyTopicAA.php',
            'label' => 'Topics',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'waste_management_dashboard.html',
            'label' => 'Waste Management',
            'roles' => [
                'Admin',
                'Supervisor'
            ],
            'permission' => 'manage_waste',
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'RMC.html',
            'label' => 'Risk Register',
            'roles' => [
                'Admin',
                'Approver',
                'Supervisor'
            ],
            'permission' => 'view_risk_register',
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'riskassessment.html',
            'label' => 'Risk Assessment',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],

        [
            'page' => 'KPIEHS_navigation.php',
            'label' => 'EHS KPI Reports',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        [
            'page' => 'agent.html',
            'label' => 'AI Agent',
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor',
                'Permit'
            ],
            'permission' => null,
            'attributes' => null,
            'category' => 'Main'
        ],
        // Example: EHS-specific menu item (attribute-based)
        // Uncomment and customize as needed:
        // [
        //     'page' => 'risk_assessments_admin.php',
        //     'label' => 'Risk Assessments Admin',
        //     'roles' => ['Admin', 'Supervisor'],
        //     'permission' => null,
        //     'attributes' => ['department' => 'EHS'],
        //     'category' => 'Main'
        // ],
    ];

    // Filter navigation items based on roles, permissions, and attributes
    $allowedNavItems = [];

    foreach ($allNavItems as $item) {
        // Check role requirement (OR logic: user needs at least one of the required roles)
        $hasRole = false;

        foreach ($item['roles'] as $requiredRole) {
            if (in_array($requiredRole, $roleNames)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            continue; // Skip if user doesn't have required role
        }

        // Check permission requirement (if specified)
        $hasPermission = true;

        if (!empty($item['permission'])) {
            $hasPermission = in_array($item['permission'], $permissionNames);
        }

        if (!$hasPermission) {
            continue; // Skip if user doesn't have required permission
        }

        // Check attribute requirements (if specified)
        $meetsAttributes = true;

        if (!empty($item['attributes'])) {
            $meetsAttributes = userMeetsAttributes($item['attributes'], $_SESSION['user_id']);
        }

        if (!$meetsAttributes) {
            continue; // Skip if user doesn't meet attribute requirements
        }

        // All checks passed - add item
        $allowedNavItems[] = $item;
    }

    // Define topbar functions with role/permission/attribute requirements
    $allTopbarFunctions = [
        'encrypt' => [
            'roles' => ['Admin'],
            'permission' => 'system_admin',
            'attributes' => null,
            'action' => 'encrypt.php'
        ],
        'backupToUSB' => [
            'roles' => [
                'Admin',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'backup'
        ],
        'clearCache' => [
            'roles' => [
                'Admin',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'clearCache'
        ],
        'topic' => [
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'SafetyTopicAA.php'
        ],
        'profile' => [
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'profile.php'
        ],
        'planner' => [
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'planner.php'
        ],
        'home' => [
            'roles' => [
                'Admin',
                'Approver',
                'User',
                'Supervisor',
                'Permit'
            ],
            'permission' => null,
            'attributes' => null,
            'action' => 'home' // Will redirect based on role
        ],
    ];

    // Filter topbar functions based on roles, permissions, and attributes
    $allowedTopbarFunctions = [];

    foreach ($allTopbarFunctions as $key => $func) {
        // Check role requirement
        $hasRole = false;

        foreach ($func['roles'] as $requiredRole) {
            if (in_array($requiredRole, $roleNames)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            continue;
        }

        // Check permission requirement
        $hasPermission = true;

        if (!empty($func['permission'])) {
            $hasPermission = in_array($func['permission'], $permissionNames);
        }

        if (!$hasPermission) {
            continue;
        }

        // Check attribute requirements
        $meetsAttributes = true;

        if (!empty($func['attributes'])) {
            $meetsAttributes = userMeetsAttributes($func['attributes'], $_SESSION['user_id']);
        }

        if (!$meetsAttributes) {
            continue;
        }

        // All checks passed
        $allowedTopbarFunctions[$key] = $func;
    }

    // Define allowed pages for navigation validation
    $allowedPages = array_column($allowedNavItems, 'page');

    // Determine home redirect based on user's primary role
    // Check for permit user (people_id = 32) first, regardless of role assignment
    $homeRedirect = 'dashboard_admin.php'; // Default
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId == 32) {
        // Permit user (people_id = 32) always goes to permit dashboard
        $homeRedirect = 'dashboard_permit.php';
    } elseif (in_array('Permit', $roleNames)) {
        $homeRedirect = 'dashboard_permit.php';
    } elseif (in_array('Admin', $roleNames)) {
        $homeRedirect = 'dashboard_admin.php';
    } elseif (!empty($roleNames)) {
        // Use first role's default dashboard
        $homeRedirect = 'dashboard_admin.php';
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'userRoles' => $roleNames,
            'userPermissions' => $permissionNames,
            'navbarItems' => $allowedNavItems,
            'topbarFunctions' => $allowedTopbarFunctions,
            'allowedPages' => array_unique($allowedPages),
            'homeRedirect' => $homeRedirect
        ]
    ]);

} catch (Exception $e) {
    error_log("Navigation Config Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load navigation configuration'
    ]);
}
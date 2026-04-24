<?php
/* File: sheener/user_permissions.php */

session_start();
require_once 'php/database.php';
require_once 'php/rbac_helper.php';

// Only allow Admin and Super Admin access
if (!isset($_SESSION['user_id'])) {
    header("Location: php/index.php");
    exit();
}

$role = $_SESSION['role'] ?? 'User';
$role_ids = $_SESSION['role_ids'] ?? [];

// Check if user has Admin or Super Admin role
$hasAccess = false;
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check for Admin or Super Admin role
    $query = "SELECT RoleID FROM roles WHERE RoleName IN ('Admin', 'Super Admin')";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $adminRoleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasAccess = !empty(array_intersect($role_ids, $adminRoleIds));
} catch (PDOException $e) {
    error_log("User Permissions Access Check Error: " . $e->getMessage());
}

if (!$hasAccess) {
    header("Location: dashboard.php");
    exit();
}

// Fetch department name
$department_id = $_SESSION['department_id'] ?? null;
$department = 'Unknown Department';

if ($department_id) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        $sql = "SELECT DepartmentName FROM departments WHERE department_id = :department_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':department_id' => $department_id]);
        $department = $stmt->fetchColumn() ?: 'Unknown Department';
    } catch (PDOException $e) {
        error_log("User Permissions Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <!-- sheener/user_permissions.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Permissions - SHEEner</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/ai-navigator.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/searchable_dropdown.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/script.js" defer></script>
    <script src="js/modal.js" defer></script>
    <script src="js/searchable_dropdown.js" defer></script>
    <link rel="shortcut icon" href="img/favicon/faviconAY.ico">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                sessionStorage.setItem('user_id', '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>');
                sessionStorage.setItem('user_role', '<?php echo $role; ?>');
            } catch(e) {
                console.error('Could not store user info in sessionStorage:', e);
            }
        });
    </script>
    <script src="js/navbar.js" defer></script>
    <script src="js/topbar.js" defer></script>
    <script src="js/ai-navigator.js" defer></script>
    <style>
        :root {
            --dashboard-primary: #2563eb;
            --dashboard-primary-dark: #1e40af;
            --dashboard-bg: #ffffff;
            --dashboard-bg-secondary: #f8fafc;
            --dashboard-border: #e2e8f0;
            --dashboard-text: #1e293b;
            --dashboard-text-secondary: #64748b;
            --dashboard-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --dashboard-radius: 0.75rem;
            --ai-navigator-width: 320px;
        }

        body {
            background-color: var(--bg-secondary, #f5f5f5);
            padding-top: 72px;
            padding-left: var(--navbar-width, 50px);
            min-height: 100vh;
            transition: padding-left 0.3s ease;
        }

        main {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            padding-right: calc(var(--ai-navigator-width) + 2rem);
            transition: padding-right 0.3s ease;
        }

        body:has(.ai-navigator-sidebar.collapsed) main {
            padding-right: calc(60px + 2rem);
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dashboard-text);
            margin: 0 0 0.5rem 0;
        }

        .dashboard-header .user-info {
            color: var(--dashboard-text-secondary);
            font-size: 0.95rem;
        }

        .permissions-container {
            background: var(--dashboard-bg);
            border-radius: var(--dashboard-radius);
            padding: 2rem;
            box-shadow: var(--dashboard-shadow);
            margin-bottom: 2rem;
        }

        .permissions-container h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            color: var(--dashboard-text);
        }

        .permissions-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--dashboard-border);
        }

        .permissions-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--dashboard-text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .permissions-tab:hover {
            color: var(--dashboard-primary);
        }

        .permissions-tab.active {
            color: var(--dashboard-primary);
            border-bottom-color: var(--dashboard-primary);
        }

        .permissions-content {
            display: none;
        }

        .permissions-content.active {
            display: block;
        }

        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .permissions-table th,
        .permissions-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--dashboard-border);
        }

        .permissions-table th {
            background: var(--dashboard-bg-secondary);
            font-weight: 600;
            color: var(--dashboard-text);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .permissions-table td {
            color: var(--dashboard-text);
        }

        .permissions-table tr:hover {
            background: var(--dashboard-bg-secondary);
        }

        .permission-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--dashboard-primary);
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--dashboard-bg-secondary);
            color: var(--dashboard-text);
        }

        .user-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--dashboard-border);
            border-radius: var(--dashboard-radius);
            font-size: 0.95rem;
            background: var(--dashboard-bg);
            color: var(--dashboard-text);
        }

        .user-select:focus {
            outline: none;
            border-color: var(--dashboard-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--dashboard-primary);
            color: white;
            border: none;
            border-radius: var(--dashboard-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .action-button:hover {
            background: var(--dashboard-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .action-button.secondary {
            background: var(--dashboard-bg-secondary);
            color: var(--dashboard-text);
            border: 2px solid var(--dashboard-border);
        }

        .action-button.secondary:hover {
            background: var(--dashboard-border);
        }

        .search-box {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--dashboard-border);
            border-radius: var(--dashboard-radius);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--dashboard-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--dashboard-text-secondary);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: var(--dashboard-radius);
            margin-bottom: 1rem;
        }

        .success-message {
            background: #efe;
            color: #3c3;
            padding: 1rem;
            border-radius: var(--dashboard-radius);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }

            .ai-navigator-sidebar {
                right: 0;
                top: 72px;
            }

            main {
                padding: 1rem;
                padding-right: 1rem;
            }

            .permissions-table {
                font-size: 0.875rem;
            }

            .permissions-table th,
            .permissions-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div id="topbar"></div>
    <div id="navbar"></div>
    
    <!-- AI Navigator Sidebar -->
    <div id="ai-navigator-container" 
         data-role="<?php echo htmlspecialchars($role); ?>"
         data-user-id="<?php echo $_SESSION['user_id']; ?>"
         data-user-name="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>">
    </div>

    <main>
        <div class="dashboard-header">
            <h1><i class="fas fa-key"></i> User Permissions Management</h1>
            <div class="user-info">
                <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($role); ?> 
                <span style="margin: 0 0.5rem;">•</span>
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($department); ?>
            </div>
        </div>

        <div id="messageContainer"></div>

        <!-- Permissions Management Tabs -->
        <div class="permissions-container">
            <div class="permissions-tabs">
                <button class="permissions-tab active" data-tab="role-permissions">
                    <i class="fas fa-user-shield"></i> Role Permissions
                </button>
                <button class="permissions-tab" data-tab="user-permissions">
                    <i class="fas fa-user"></i> User Permissions
                </button>
                <button class="permissions-tab" data-tab="permission-list">
                    <i class="fas fa-list"></i> All Permissions
                </button>
            </div>

            <!-- Role Permissions Tab -->
            <div id="role-permissions" class="permissions-content active">
                <h3>Manage Permissions by Role</h3>
                <p style="color: var(--dashboard-text-secondary); margin-bottom: 1rem;">
                    Assign permissions to roles. All users with a role will inherit its permissions.
                </p>
                <div id="rolePermissionsContent">
                    <div class="loading">Loading roles and permissions...</div>
                </div>
            </div>

            <!-- User Permissions Tab -->
            <div id="user-permissions" class="permissions-content">
                <h3>View User Permissions</h3>
                <p style="color: var(--dashboard-text-secondary); margin-bottom: 1rem;">
                    Select a user from the dropdown below to view their permissions in a modal (inherited from their roles).
                </p>
                <div style="margin-bottom: 1rem;">
                    <label for="userSelectContainer" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        Select User:
                    </label>
                    <div id="userSelectContainer" data-name="selected_user"></div>
                </div>
                <p style="color: var(--dashboard-text-secondary); text-align: center; padding: 2rem; font-style: italic;">
                    Select a user from the dropdown above to view their permissions
                </p>
            </div>

            <!-- Permission List Tab -->
            <div id="permission-list" class="permissions-content">
                <h3>All System Permissions</h3>
                <p style="color: var(--dashboard-text-secondary); margin-bottom: 1rem;">
                    View and manage all available permissions in the system.
                </p>
                <input type="text" id="permissionSearch" class="search-box" placeholder="Search permissions...">
                <div id="permissionListContent">
                    <div class="loading">Loading permissions...</div>
                </div>
            </div>
        </div>
    </main>

    <!-- User Permissions Modal -->
    <div id="userPermissionsModal" class="modal-overlay hidden">
        <div class="modal-content modal-lg">
            <h3 class="modal-header">
                <div class="title-text">User Permissions</div>
                <div class="header-icons">
                    <img src="img/close.svg" alt="Close Icon" onclick="closeUserPermissionsModal()" class="edit-icon">
                </div>
            </h3>
            <div class="modal-body" id="userPermissionsModalBody">
                <div class="loading">Loading permissions...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserPermissionsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let roles = [];
        let permissions = [];
        let rolePermissions = {};
        let users = [];
        let userSelectHandler = null;
        let isProcessingUserSelection = false;
        let userSelectDropdown = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            setupTabs();
            loadInitialData();
        });

        function setupTabs() {
            const tabs = document.querySelectorAll('.permissions-tab');
            const contents = document.querySelectorAll('.permissions-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;

                    // Update tab states
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));

                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');

                    // Load content for active tab
                    if (targetTab === 'role-permissions') {
                        if (roles.length > 0 && permissions.length > 0) {
                            renderRolePermissions();
                        } else {
                            loadRolePermissions();
                        }
                    } else if (targetTab === 'user-permissions') {
                        // Load users if not already loaded
                        if (users.length === 0 || !userSelectDropdown) {
                            loadUsers();
                        }
                    } else if (targetTab === 'permission-list') {
                        loadPermissionList();
                    }
                });
            });
        }

        async function loadInitialData() {
            try {
                await Promise.all([
                    loadRoles(),
                    loadPermissions(),
                    loadRolePermissions()
                ]);
            } catch (error) {
                console.error('Error loading initial data:', error);
                showMessage('Error loading data. Please refresh the page.', 'error');
            }
        }

        async function loadRoles() {
            try {
                const response = await fetch('php/get_roles.php');
                const data = await response.json();
                if (data.success) {
                    roles = data.data || [];
                }
            } catch (error) {
                console.error('Error loading roles:', error);
            }
        }

        async function loadPermissions() {
            try {
                const response = await fetch('php/get_permissions.php');
                const data = await response.json();
                if (data.success) {
                    permissions = data.data || [];
                }
            } catch (error) {
                console.error('Error loading permissions:', error);
            }
        }

        async function loadRolePermissions() {
            try {
                const response = await fetch('php/get_role_permissions.php');
                const data = await response.json();
                if (data.success) {
                    rolePermissions = {};
                    (data.data || []).forEach(rp => {
                        if (!rolePermissions[rp.RoleID]) {
                            rolePermissions[rp.RoleID] = [];
                        }
                        rolePermissions[rp.RoleID].push(rp.PermissionID);
                    });
                    renderRolePermissions();
                }
            } catch (error) {
                console.error('Error loading role permissions:', error);
                document.getElementById('rolePermissionsContent').innerHTML = 
                    '<div class="error-message">Error loading role permissions</div>';
            }
        }

        function renderRolePermissions() {
            const container = document.getElementById('rolePermissionsContent');
            
            if (roles.length === 0 || permissions.length === 0) {
                container.innerHTML = '<div class="loading">Loading...</div>';
                return;
            }

            let html = '<table class="permissions-table"><thead><tr><th>Permission</th>';
            roles.forEach(role => {
                html += `<th>${role.RoleName}</th>`;
            });
            html += '</tr></thead><tbody>';

            permissions.forEach(permission => {
                html += `<tr><td><strong>${permission.PermissionName}</strong></td>`;
                roles.forEach(role => {
                    const hasPermission = rolePermissions[role.RoleID]?.includes(permission.PermissionID) || false;
                    html += `<td style="text-align: center;">
                        <input type="checkbox" 
                               class="permission-checkbox" 
                               data-role-id="${role.RoleID}" 
                               data-permission-id="${permission.PermissionID}"
                               ${hasPermission ? 'checked' : ''}
                               onchange="toggleRolePermission(${role.RoleID}, ${permission.PermissionID}, this.checked)">
                    </td>`;
                });
                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        async function toggleRolePermission(roleId, permissionId, enabled) {
            try {
                const response = await fetch('php/toggle_role_permission.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        role_id: roleId,
                        permission_id: permissionId,
                        enabled: enabled
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Update local state
                    if (!rolePermissions[roleId]) {
                        rolePermissions[roleId] = [];
                    }
                    if (enabled) {
                        if (!rolePermissions[roleId].includes(permissionId)) {
                            rolePermissions[roleId].push(permissionId);
                        }
                    } else {
                        rolePermissions[roleId] = rolePermissions[roleId].filter(id => id !== permissionId);
                    }
                    showMessage(data.message || 'Permission updated successfully', 'success');
                } else {
                    showMessage(data.error || 'Error updating permission', 'error');
                    // Revert checkbox
                    const checkbox = document.querySelector(`input[data-role-id="${roleId}"][data-permission-id="${permissionId}"]`);
                    if (checkbox) checkbox.checked = !enabled;
                }
            } catch (error) {
                console.error('Error toggling permission:', error);
                showMessage('Error updating permission', 'error');
                // Revert checkbox
                const checkbox = document.querySelector(`input[data-role-id="${roleId}"][data-permission-id="${permissionId}"]`);
                if (checkbox) checkbox.checked = !enabled;
            }
        }

        async function loadUsers() {
            try {
                // Only load users if not already loaded
                if (users.length === 0) {
                    const response = await fetch('php/get_people.php');
                    const data = await response.json();
                    if (data.success) {
                        users = data.data || [];
                    }
                }
                
                const container = document.getElementById('userSelectContainer');
                if (!container) return;
                
                // Prepare user data for searchable dropdown
                const userData = users.map(user => {
                    const name = `${user.first_name || user.FirstName || ''} ${user.last_name || user.LastName || ''}`.trim();
                    const email = user.email || user.Email || '';
                    return {
                        id: user.people_id || user.PersonID,
                        name: `${name}${email ? ' (' + email + ')' : ''}`
                    };
                });
                
                // Initialize or reinitialize searchable dropdown
                if (userSelectDropdown) {
                    // Update existing dropdown data
                    userSelectDropdown.options.data = userData;
                    userSelectDropdown.filteredData = [...userData];
                    userSelectDropdown.render();
                } else {
                    // Create new searchable dropdown
                    userSelectDropdown = new SearchableDropdown('userSelectContainer', {
                        placeholder: 'Type to search and select a user...',
                        data: userData,
                        displayField: 'name',
                        valueField: 'id',
                        onSelect: function(item) {
                            if (isProcessingUserSelection) {
                                return;
                            }
                            
                            if (item && item.id) {
                                isProcessingUserSelection = true;
                                
                                // Open modal with selected user
                                openUserPermissionsModal(item.id);
                                
                                // Clear selection after opening modal
                                setTimeout(() => {
                                    if (userSelectDropdown) {
                                        userSelectDropdown.clear();
                                    }
                                    isProcessingUserSelection = false;
                                }, 500);
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading users:', error);
                const container = document.getElementById('userSelectContainer');
                if (container) {
                    container.innerHTML = '<p style="color: var(--dashboard-text-secondary);">Error loading users</p>';
                }
            }
        }

        function openUserPermissionsModal(userId) {
            if (!userId) {
                isProcessingUserSelection = false;
                return;
            }

            // Open modal
            const modal = document.getElementById('userPermissionsModal');
            if (!modal) {
                isProcessingUserSelection = false;
                return;
            }

            // Prevent multiple opens
            if (!modal.classList.contains('hidden')) {
                isProcessingUserSelection = false;
                return;
            }

            if (typeof modalManager !== 'undefined') {
                modalManager.open('userPermissionsModal');
            } else {
                modal.classList.remove('hidden');
            }

            // Load permissions into modal
            loadUserPermissions(userId);
        }

        function closeUserPermissionsModal() {
            const modal = document.getElementById('userPermissionsModal');
            if (modal) {
                if (typeof modalManager !== 'undefined') {
                    modalManager.close('userPermissionsModal');
                } else {
                    modal.classList.add('hidden');
                }
            }
            // Reset processing flag
            isProcessingUserSelection = false;
        }

        async function loadUserPermissions(userId) {
            if (!userId) {
                isProcessingUserSelection = false;
                return Promise.resolve();
            }

            const container = document.getElementById('userPermissionsModalBody');
            if (!container) {
                isProcessingUserSelection = false;
                return Promise.resolve();
            }

            container.innerHTML = '<div class="loading">Loading user permissions...</div>';

            try {
                const response = await fetch(`php/get_user_permissions.php?user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    const userPerms = data.data || [];
                    const user = users.find(u => (u.people_id || u.PersonID) == userId);
                    const userName = user ? `${user.first_name || user.FirstName || ''} ${user.last_name || user.LastName || ''}`.trim() : 'User';
                    const userEmail = user ? (user.email || user.Email || '') : '';

                    if (userPerms.length === 0) {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 2rem;">
                                <h4 style="margin-bottom: 1rem; color: var(--dashboard-text);">
                                    <i class="fas fa-user"></i> ${userName}
                                </h4>
                                ${userEmail ? `<p style="color: var(--dashboard-text-secondary); margin-bottom: 1rem;">${userEmail}</p>` : ''}
                                <p style="color: var(--dashboard-text-secondary);">
                                    This user has no permissions assigned.
                                </p>
                            </div>
                        `;
                        return Promise.resolve();
                    }

                    // Remove duplicates (same permission from multiple roles)
                    const uniquePerms = [];
                    const seenPerms = new Set();
                    
                    userPerms.forEach(perm => {
                        const key = perm.PermissionID;
                        if (!seenPerms.has(key)) {
                            seenPerms.add(key);
                            uniquePerms.push(perm);
                        }
                    });

                    let html = `
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="margin-bottom: 0.5rem; color: var(--dashboard-text);">
                                <i class="fas fa-user"></i> ${userName}
                            </h4>
                            ${userEmail ? `<p style="color: var(--dashboard-text-secondary); margin: 0;">${userEmail}</p>` : ''}
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong>Total Permissions: ${uniquePerms.length}</strong>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="permissions-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Permission Name</th>
                                        <th>Source Role(s)</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    uniquePerms.forEach(perm => {
                        // Get all roles that grant this permission
                        const rolesForPerm = userPerms
                            .filter(p => p.PermissionID === perm.PermissionID)
                            .map(p => p.RoleName)
                            .filter(Boolean);
                        
                        html += `
                            <tr>
                                <td><strong>${perm.PermissionName}</strong></td>
                                <td>${rolesForPerm.map(r => `<span class="role-badge">${r}</span>`).join(' ')}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `<div class="error-message">${data.error || 'Error loading user permissions'}</div>`;
                }
            } catch (error) {
                console.error('Error loading user permissions:', error);
                container.innerHTML = '<div class="error-message">Error loading user permissions</div>';
            } finally {
                // Ensure processing flag is reset after loading completes
                setTimeout(() => {
                    isProcessingUserSelection = false;
                }, 100);
            }
        }

        let permissionSearchHandler = null;

        async function loadPermissionList() {
            const container = document.getElementById('permissionListContent');
            
            if (permissions.length === 0) {
                await loadPermissions();
            }

            renderPermissionList();

            // Setup search - remove old listener if exists
            const searchInput = document.getElementById('permissionSearch');
            if (searchInput) {
                if (permissionSearchHandler) {
                    searchInput.removeEventListener('input', permissionSearchHandler);
                }
                permissionSearchHandler = function() {
                    renderPermissionList(this.value.toLowerCase());
                };
                searchInput.addEventListener('input', permissionSearchHandler);
            }
        }

        function renderPermissionList(searchTerm = '') {
            const container = document.getElementById('permissionListContent');
            const filtered = permissions.filter(p => 
                !searchTerm || p.PermissionName.toLowerCase().includes(searchTerm)
            );

            if (filtered.length === 0) {
                container.innerHTML = '<p style="color: var(--dashboard-text-secondary); text-align: center; padding: 2rem;">No permissions found</p>';
                return;
            }

            let html = '<table class="permissions-table"><thead><tr><th>Permission ID</th><th>Permission Name</th></tr></thead><tbody>';
            filtered.forEach(perm => {
                html += `<tr>
                    <td>${perm.PermissionID}</td>
                    <td><strong>${perm.PermissionName}</strong></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function showMessage(message, type = 'success') {
            const container = document.getElementById('messageContainer');
            const className = type === 'success' ? 'success-message' : 'error-message';
            container.innerHTML = `<div class="${className}">${message}</div>`;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>


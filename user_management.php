<?php
/* File: sheener/user_management.php */

session_start();
require_once 'php/database.php';
require_once 'php/rbac_helper.php';

// Only allow Admin access
if (!isset($_SESSION['user_id'])) {
    header("Location: php/index.php");
    exit();
}

$role = $_SESSION['role'] ?? 'User';
if ($role !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Get role information from session
$role_id = $_SESSION['role_id'] ?? null;

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
        error_log("User Management Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <!-- sheener/user_management.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SHEEner</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/ai-navigator.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/script.js" defer></script>
    <script src="js/modal.js" defer></script>
    <link rel="shortcut icon" href="img/favicon/faviconAY.ico">
    <script>
        // Store user info in sessionStorage for navbar/topbar detection
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

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        @media (min-width: 1200px) {
            .dashboard-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 1600px) {
            .dashboard-stats {
                grid-template-columns: repeat(4, 1fr);
                gap: 1.5rem;
            }
        }

        .stat-card {
            background: var(--dashboard-bg);
            border-radius: var(--dashboard-radius);
            padding: 1.25rem;
            box-shadow: var(--dashboard-shadow);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 140px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.15);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .stat-card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dashboard-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card-icon.primary { background: rgba(37, 99, 235, 0.1); color: var(--dashboard-primary); }
        .stat-card-icon.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-card-icon.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-card-icon.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .stat-card-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--dashboard-text);
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .stat-card-label {
            font-size: 0.875rem;
            color: var(--dashboard-text-secondary);
        }

        .quick-actions {
            background: var(--dashboard-bg);
            border-radius: var(--dashboard-radius);
            padding: 2rem;
            box-shadow: var(--dashboard-shadow);
        }

        .quick-actions h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            color: var(--dashboard-text);
        }

        .action-grid {
            display: flex;
            flex-direction: row;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: var(--dashboard-border) transparent;
        }

        .action-grid::-webkit-scrollbar {
            height: 6px;
        }

        .action-grid::-webkit-scrollbar-track {
            background: transparent;
        }

        .action-grid::-webkit-scrollbar-thumb {
            background: var(--dashboard-border);
            border-radius: 3px;
        }

        .action-grid::-webkit-scrollbar-thumb:hover {
            background: var(--dashboard-text-secondary);
        }

        .action-button {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: var(--dashboard-bg-secondary);
            border: 2px solid var(--dashboard-border);
            border-radius: var(--dashboard-radius);
            text-decoration: none;
            color: var(--dashboard-text);
            transition: all 0.2s;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: fit-content;
        }

        .action-button:hover {
            background: var(--dashboard-primary);
            color: white;
            border-color: var(--dashboard-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .action-button i {
            font-size: 1.5rem;
        }

        .action-button span {
            font-weight: 600;
            font-size: 0.95rem;
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

            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .action-grid {
                flex-direction: row;
                gap: 0.75rem;
            }

            .action-button {
                padding: 0.875rem 1.25rem;
            }

            .action-button i {
                font-size: 1.25rem;
            }

            .action-button span {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div id="topbar"></div>
    <div id="navbar"></div>
    
    <!-- AI Navigator Sidebar (Reusable Component) -->
    <div id="ai-navigator-container" 
         data-role="<?php echo htmlspecialchars($role); ?>"
         data-user-id="<?php echo $_SESSION['user_id']; ?>"
         data-user-name="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>">
    </div>

    <main>
        <div class="dashboard-header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <div class="user-info">
                <i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($role); ?> 
                <span style="margin: 0 0.5rem;">•</span>
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($department); ?>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="dashboard-stats" id="userStats">
            <!-- Populated by JavaScript -->
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions" style="margin-top: 2rem; margin-bottom: 0;">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-grid" id="quickActions">
                <a href="people_list.php" class="action-button">
                    <i class="fas fa-users"></i>
                    <span>View All Users</span>
                </a>
                <a href="people_list.php" class="action-button" onclick="event.preventDefault(); openAddUserModal();">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New User</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('Role Management - Coming Soon');">
                    <i class="fas fa-user-shield"></i>
                    <span>Manage Roles</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('Department Management - Coming Soon');">
                    <i class="fas fa-building"></i>
                    <span>Manage Departments</span>
                </a>
                <a href="user_permissions.php" class="action-button">
                    <i class="fas fa-key"></i>
                    <span>User Permissions</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('Activity Logs - Coming Soon');">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
            </div>
        </div>

        <!-- User Management Tools -->
        <div class="quick-actions" style="margin-top: 2rem; margin-bottom: 0;">
            <h3><i class="fas fa-tools"></i> Management Tools</h3>
            <div class="action-grid" id="managementTools">
                <a href="people_list.php" class="action-button">
                    <i class="fas fa-list"></i>
                    <span>People List</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('Bulk User Import - Coming Soon');">
                    <i class="fas fa-file-import"></i>
                    <span>Bulk Import</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('User Export - Coming Soon');">
                    <i class="fas fa-file-export"></i>
                    <span>Export Users</span>
                </a>
                <a href="#" class="action-button" onclick="event.preventDefault(); alert('Password Reset - Coming Soon');">
                    <i class="fas fa-unlock-alt"></i>
                    <span>Password Reset</span>
                </a>
            </div>
        </div>
    </main>

    <script>
        const userRole = '<?php echo $role; ?>';
        const userId = '<?php echo $_SESSION['user_id']; ?>';
        const userName = '<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>';

        // Initialize user management page
        document.addEventListener('DOMContentLoaded', function() {
            loadUserStats();
        });

        function openAddUserModal() {
            // Redirect to people_list.php which should have add user functionality
            window.location.href = 'people_list.php';
        }

        async function loadUserStats() {
            try {
                const response = await fetch('php/get_people.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    const users = data.data;
                    const totalUsers = users.length;
                    const activeUsers = users.filter(u => !u.status || u.status === 'Active').length;
                    
                    // Count unique roles
                    const roles = new Set(users.map(u => u.role || u.RoleName).filter(Boolean));
                    const uniqueRoles = roles.size;
                    
                    // Count unique departments
                    const departments = new Set(users.map(u => u.department_id || u.DepartmentName).filter(Boolean));
                    const uniqueDepartments = departments.size;
                    
                    const userStats = document.getElementById('userStats');
                    userStats.innerHTML = `
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Total Users</div>
                                <div class="stat-card-icon primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">${totalUsers}</div>
                            <div class="stat-card-label">Registered users</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Active Users</div>
                                <div class="stat-card-icon success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">${activeUsers}</div>
                            <div class="stat-card-label">${totalUsers - activeUsers} inactive</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">User Roles</div>
                                <div class="stat-card-icon warning">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">${uniqueRoles}</div>
                            <div class="stat-card-label">Different roles</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Departments</div>
                                <div class="stat-card-icon danger">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">${uniqueDepartments}</div>
                            <div class="stat-card-label">Active departments</div>
                        </div>
                    `;
                } else {
                    // Fallback if API doesn't return expected format
                    const userStats = document.getElementById('userStats');
                    userStats.innerHTML = `
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Total Users</div>
                                <div class="stat-card-icon primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">-</div>
                            <div class="stat-card-label">Loading...</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Active Users</div>
                                <div class="stat-card-icon success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">-</div>
                            <div class="stat-card-label">Loading...</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">User Roles</div>
                                <div class="stat-card-icon warning">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">-</div>
                            <div class="stat-card-label">Loading...</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <div class="stat-card-title">Departments</div>
                                <div class="stat-card-icon danger">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">-</div>
                            <div class="stat-card-label">Loading...</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading user stats:', error);
                const userStats = document.getElementById('userStats');
                userStats.innerHTML = '<p style="color: var(--dashboard-text-secondary); grid-column: 1 / -1;">Unable to load user statistics</p>';
            }
        }
    </script>
</body>
</html>


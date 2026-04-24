<?php
/* File: sheener/php/dashboard.php */

session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role_id = $_SESSION['role_id'];
$department_id = $_SESSION['department_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch role name
    $sql = "SELECT RoleName FROM roles WHERE RoleID = :role_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':role_id' => $role_id]);
    $role = $stmt->fetchColumn() ?: 'Unknown Role';

    // Fetch department name
    $sql = "SELECT DepartmentName FROM departments WHERE department_id = :department_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':department_id' => $department_id]);
    $department = $stmt->fetchColumn() ?: 'Unknown Department';
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<?php
$page_title = 'SHEEner Dashboard';
$use_ai_navigator = false;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = ['js/script.js'];
include 'includes/header.php';
?>


    <main>
        <section class="dashboard-content">
            <h2>Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h2>
            <p>Role: <?php echo $role; ?></p>
            <p>Department: <?php echo $department; ?></p>
            <p>Here you can manage tasks, view reports, and track productivity.</p>

            <?php if ($role === 'Admin'): ?>
                <a href="admin_panel.php">Admin Panel</a>
            <?php elseif ($role === 'Manager'): ?>
                <a href="manager_tools.php">Manager Tools</a>
            <?php else: ?>
                <a href="employee_tasks.php">Your Tasks</a>
            <?php endif; ?>
        </section>
    </main>
<?php include 'includes/footer.php'; ?>

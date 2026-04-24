<?php
/* File: sheener/dashboard_manager.php */

session_start();
// Block Permit user (people_id = 32) from accessing manager dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 32) {
    header("Location: dashboard_permit.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<?php
$page_title = 'Manager Dashboard';
$use_ai_navigator = false;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = ['js/script.js', 'js/planner.js', 'js/graph.js'];
include 'includes/header.php';
?>


    <main>
        <section class="dashboard-content">
            <h2>Welcome, Manager</h2>
            <p>Here you can manage users, view statistics, and configure settings.</p>
            <!-- Add admin-specific content here -->
        </section>
    </main>
<?php include 'includes/footer.php'; ?>

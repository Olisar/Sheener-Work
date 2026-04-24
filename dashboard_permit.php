<?php
/* File: sheener/dashboard_permit.php */

session_start();
require_once 'php/database.php';

// --- Access control: Permit user (people_id = 32) or Admins (role_id = 1) ---

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Allow Permit user (people_id = 32) to access
$isPermitUser = ((int)$_SESSION['user_id'] === 32);

// If not Permit user, check for Admin role
if (!$isPermitUser) {
    // Require role_id for non-Permit users
    if (!isset($_SESSION['role_id'])) {
        header("Location: index.php");
        exit();
    }
    
    // Only allow Admin role (RoleID = 1) to access PTW dashboard
    if ((int)$_SESSION['role_id'] !== 1) {
        // Logged in but not Admin – redirect to their normal dashboard
        header("Location: dashboard.php");
        exit();
    }
}

// Additional security: verify user still exists in DB
try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT people_id, FirstName, LastName 
              FROM people 
              WHERE people_id = :people_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':people_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // User not found anymore – kill session and force re-login
        session_destroy();
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permit to Work Management | Dashboard</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/searchable_dropdown.css">
    <script src="js/script.js" defer></script>
    <link rel="shortcut icon" href="img/favicon/faviconAY.ico">
    <script src="js/navbar.js" defer></script>
    <script src="js/topbar.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4387f5;
            --accent: #00c6ff;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #383838, #1a1a1a);
            background-attachment: fixed;
            color: #ffffff;
            margin: 0;
            padding: 10px 40px 40px 40px;
            min-height: 100vh;
        }

        main {
            padding-top: 5px;
        }

        .dashboard-content {
            margin-top: 5px;
        }

        .dashboard-header {
            text-align: center;
            margin-top: 0;
            margin-bottom: 30px;
            position: relative;
        }

        .dashboard-header h2 {
            font-size: 3.2rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(to right, #ffffff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1.5px;
        }

        .dashboard-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
            margin-top: 15px;
        }

        .permit-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .permit-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            box-sizing: border-box;
            position: relative;
        }

        .permit-card:hover {
            transform: translateY(-15px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            border-color: rgba(67, 135, 245, 0.5);
        }

        .permit-card h3 {
            margin: 0 0 20px 0;
            color: #ffffff;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .permit-card h3 i {
            color: var(--primary);
            background: rgba(67, 135, 245, 0.15);
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 1.6rem;
        }

        .permit-card p {
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.8;
            margin: 0 0 35px 0;
            font-size: 1rem;
            flex-grow: 1;
        }

        .permit-card .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s;
            gap: 12px;
            width: fit-content;
        }

        .permit-card:hover .btn-action {
            background: #2a6edb;
            box-shadow: 0 10px 25px rgba(67, 135, 245, 0.5);
            transform: scale(1.05);
        }

        .restricted-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            padding: 20px 30px;
            margin-bottom: 50px;
            border-radius: 16px;
            color: #ffc107;
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 600;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .permit-card.disabled-card {
            opacity: 0.4;
            pointer-events: none;
            filter: grayscale(1);
        }

        .permit-card.disabled-card::after {
            display: none;
        }
    </style>
</head>
<body>
    <div id="topbar"></div>
    <div id="navbar"></div>

    <main>
        <section class="dashboard-content">
            <div class="dashboard-header">
                <h2><i class="fas fa-file-contract"></i> Permit Management</h2>
                <p>Enterprise Control Center for Permit-to-Work Workflows</p>
            </div>

            <?php if ($isPermitUser): ?>
                <div class="restricted-notice">
                    <i class="fas fa-info-circle fa-2x"></i>
                    <div>
                        <strong>Restricted Access Mode:</strong> You are logged in as a dedicated permit management account. Your access is optimized for PTW workflows.
                    </div>
                </div>
            <?php endif; ?>

            <div class="permit-dashboard-grid">
                <div class="permit-card">
                    <h3><i class="fas fa-list-ul"></i> Permit List</h3>
                    <p>Review and manage all active safety permits, search existing records, and monitor approval statuses across all departments.</p>
                    <a href="permit_list.php" class="btn-action">View Permit List <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="permit-card">
                    <h3><i class="fas fa-plus-circle"></i> Create Permit</h3>
                    <p>Initiate a new permit issuance safety flow. Select from scheduled tasks or define new operational requirements in real-time.</p>
                    <a href="#" onclick="openTaskSearchModal(); return false;" class="btn-action">Issue New Permit <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="permit-card disabled-card">
                    <h3><i class="fas fa-flask"></i> Testing Portal</h3>
                    <p>Access the latest development versions of the permit creation flow for internal quality assurance and feedback collections.</p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/modals/permit_creation_flow.php'; ?>
    <script src="js/permit_flow.js"></script>
    <script src="js/permit_manager.js"></script>
</body>
</html>

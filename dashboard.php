<?php
/* File: sheener/dashboard.php */

session_start();
require_once 'php/database.php';
require_once 'php/rbac_helper.php';

// Block Permit user from accessing main dashboard
if (isPermitUser()) {
    header("Location: dashboard_permit.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: php/index.php");
    exit();
}

$role = $_SESSION['role'] ?? 'User';
$role_id = $_SESSION['role_id'] ?? null;
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
        error_log("Portal Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Portal | Dashboard</title>
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/ai-navigator.css">
    <link rel="stylesheet" href="css/ui-standard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/script.js" defer></script>
    <link rel="shortcut icon" href="img/favicon/faviconAY.ico">
    <script src="js/navbar.js" defer></script>
    <script src="js/topbar.js" defer></script>
    <script src="js/ai-navigator.js" defer></script>
    <!-- Importing modern font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-border: transparent;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --accent-glow: #00c6ff;
            --font-main: 'Outfit', sans-serif;
        }

        body {
            font-family: var(--font-main);
            background: radial-gradient(circle at top right, #525252, #383838);
            background-attachment: fixed;
            color: var(--text-primary);
            margin: 0;
            overflow-x: hidden;
        }

        main {
            padding: 85px 30px 60px 30px !important;
            margin-left: 70px !important; 
            width: calc(100% - 70px) !important;
            max-width: none !important;
            box-sizing: border-box !important;
            margin-top: 0 !important;
        }

        .page-header {
            margin-top: 0 !important;
            padding: 15px 25px !important;
            min-height: 50px !important;
            border-radius: 12px !important;
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 20px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important;
        }

        .page-header h1 {
            font-size: 1.3rem !important;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(to right, #ffffff, #00c6ff);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-top: 10px;
        }

        /* Filter Tabs Navigation */
        .filter-container {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            padding: 10px;
            backdrop-filter: blur(10px);
            border-radius: 50px;
            background: rgba(12, 81, 121, 0.3);
            max-width: fit-content;
            margin-left: auto;
            margin-right: auto;
            border: none;
        }

        .filter-tab {
            padding: 6px 16px !important;
            border-radius: 30px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            transition: all 0.3s ease !important;
            color: var(--text-secondary) !important;
            background: rgb(12, 81, 121) !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            border: none !important;
        }

        .filter-tab i {
            font-size: 0.9rem !important;
            transition: transform 0.3s ease;
        }

        .filter-tab[data-category="dashboard"] i { color: #ffffff; }
        .filter-tab[data-category="ops"] i { color: #00c6ff; }
        .filter-tab[data-category="comp"] i { color: #50fa7b; }
        .filter-tab[data-category="ent"] i { color: #bd93f9; }
        .filter-tab[data-category="util"] i { color: #ffb86c; }

        .filter-tab:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .filter-tab[data-category="dashboard"]:hover { background: rgba(0, 198, 255, 0.2) !important; }
        .filter-tab[data-category="ops"]:hover { background: rgba(0, 198, 255, 0.2) !important; }
        .filter-tab[data-category="comp"]:hover { background: rgba(80, 250, 123, 0.2) !important; }
        .filter-tab[data-category="ent"]:hover { background: rgba(189, 147, 249, 0.2) !important; }
        .filter-tab[data-category="util"]:hover { background: rgba(255, 184, 108, 0.2) !important; }

        .filter-tab:hover i {
            transform: scale(1.1);
        }

        .filter-tab.active {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            outline: none;
        }

        .filter-tab[data-category="dashboard"].active { background: #00c6ff !important; color: #050510 !important; }
        .filter-tab[data-category="ops"].active { background: #00c6ff !important; color: #050510 !important; }
        .filter-tab[data-category="comp"].active { background: #50fa7b !important; color: #050510 !important; }
        .filter-tab[data-category="ent"].active { background: #bd93f9 !important; color: #050510 !important; }
        .filter-tab[data-category="util"].active { background: #ffb86c !important; color: #050510 !important; }

        .filter-tab.active i, .filter-tab.active span {
            color: #050510 !important;
            filter: none !important;
        }

        .filter-tab:focus {
            outline: none;
        }

        /* Section Visibility & Transitions */
        .section-group {
            margin-bottom: 60px;
            display: none;
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.5s ease;
        }

        .section-group.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1), transparent);
            display: none;
        }

        /* Grid Layout - Three Columns */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        /* Action Card Styling */
        .action-card {
            background: var(--card-bg) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 15px !important;
            text-decoration: none !important;
            color: inherit !important;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
            display: flex !important;
            flex-direction: column !important;
            position: relative !important;
            overflow: hidden !important;
            cursor: pointer !important;
        }

        .action-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border: none;
            outline: none;
        }

        /* White Icons Styling */
        .card-icon {
            font-size: 1.6rem !important;
            margin-bottom: 10px !important;
            width: 40px !important;
            height: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 10px !important;
            background: rgba(255, 240, 156, 0.205) !important;
            transition: transform 0.3s ease !important;
            filter: grayscale(1) brightness(10) !important;
            color: white !important;
        }

        .action-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .card-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        #ent .card-icon {
            background: rgb(255, 239, 170);
            filter: none;
        }

        #ent .card-icon img {
            filter: none;
        }

        #ent .card-icon img[src*="1jde.png"] {
            filter: brightness(0) saturate(100%) invert(18%) sepia(87%) saturate(7403%) hue-rotate(358deg) brightness(101%) contrast(116%);
        }

        #ent .card-icon img[src*="1sign.png"] {
            filter: none;
        }

        .action-card[onclick*="fgas-licensing"] .card-icon,
        .card-icon img[src*="1HFA.png"] {
            filter: brightness(0) saturate(100%) invert(72%) sepia(35%) saturate(3065%) hue-rotate(130deg) brightness(98%) contrast(85%);
        }

        .card-icon img[src*="RA.png"] {
            filter: none;
        }

        .card-info h3 {
            margin: 0 !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            margin-bottom: 4px !important;
            color: white !important;
        }

        .card-info p {
            margin: 0 !important;
            font-size: 0.8rem !important;
            color: var(--text-secondary) !important;
            line-height: 1.3 !important;
        }

        .card-tag {
            position: absolute;
            top: 25px;
            right: 25px;
            font-size: 0.65rem;
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.8px;
            background: rgba(255, 255, 255, 0.05);
            border: none;
        }

        .external-badge {
            display: inline-block;
            margin-top: 15px;
            font-size: 0.7rem;
            color: var(--accent-glow);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cat-ops { color: #00c6ff; background: rgba(0, 198, 255, 0.1); border: none; }
        .cat-comp { color: #50fa7b; background: rgba(80, 250, 123, 0.1); border: none; }
        .cat-ent { color: #bd93f9; background: rgba(189, 147, 249, 0.1); border: none; }
        .cat-util { color: #ffb86c; background: rgba(255, 184, 108, 0.1); border: none; }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg) !important;
            backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            padding: 0.85rem !important;
            transition: all 0.2s !important;
            display: flex !important;
            flex-direction: column !important;
            min-height: 90px !important;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.12);
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
            color: var(--text-secondary);
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

        .stat-card-icon.primary { background: rgba(0, 198, 255, 0.1); color: #00c6ff; }
        .stat-card-icon.success { background: rgba(80, 250, 123, 0.1); color: #50fa7b; }
        .stat-card-icon.warning { background: rgba(255, 184, 108, 0.1); color: #ffb86c; }
        .stat-card-icon.danger { background: rgba(255, 85, 85, 0.1); color: #ff5555; }

        .stat-card-value {
            font-size: 1.4rem !important;
            font-weight: 700 !important;
            color: white !important;
            margin-bottom: 0 !important;
        }

        .stat-card-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .quick-actions {
            background: transparent;
            margin-top: 2rem;
        }

        .quick-actions h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .action-button {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.75rem 1rem !important;
            background: var(--card-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            text-decoration: none !important;
            color: white !important;
            transition: all 0.2s !important;
            cursor: pointer !important;
            white-space: nowrap !important;
            font-size: 0.85rem !important;
        }

        .action-button:hover {
            background: var(--accent-glow);
            color: #050510;
            transform: translateY(-2px);
        }

        .workflow-steps {
            justify-content: center !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 0.5rem !important;
            overflow-x: auto !important;
            padding-bottom: 5px !important;
        }

        .workflow-column {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 5px !important;
        }

        .workflow-column > span {
            font-size: 9px !important;
        }

        .workflow-arrow {
            color: var(--text-secondary) !important;
            font-size: 0.9rem !important;
            padding: 0 4px !important;
        }

        .workflow-arrow-cycle { color: var(--accent-glow); }

        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .filter-container { border-radius: 20px; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .stat-card { min-height: auto; }
        }
    </style>
</head>

<body>
    <div id="topbar"></div>
    <div id="navbar"></div>
    <div id="ai-navigator-container" 
         data-role="<?php echo htmlspecialchars($role); ?>"
         data-user-id="<?php echo $_SESSION['user_id']; ?>"
         data-user-name="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>">
    </div>

    <main>
        <header class="page-header">
            <h1 style="font-size: 1.8rem;"><i class="fas fa-th-large"></i> Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></h1>
            <div class="header-actions">
                <p style="margin: 0; font-size: 14px; opacity: 0.8; color: white;"><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($role); ?> • <i class="fas fa-building"></i> <?php echo htmlspecialchars($department); ?></p>
            </div>
        </header>

        <div class="filter-container">
            <button class="filter-tab active" data-category="dashboard">
                <i class="fas fa-th-large"></i> Dashboard
            </button>
            <button class="filter-tab" data-category="ops">
                <i class="fas fa-tools"></i> Operations
            </button>
            <button class="filter-tab" data-category="comp">
                <i class="fas fa-shield-alt"></i> Compliance
            </button>
            <button class="filter-tab" data-category="ent">
                <i class="fas fa-building"></i> Enterprise
            </button>
            <button class="filter-tab" data-category="util">
                <i class="fas fa-sparkles"></i> Utilities
            </button>
        </div>

        <section class="section-group active" id="dashboard">
            <div class="dashboard-stats" id="dashboardStats"></div>

            <div class="quick-actions" style="margin-top: 2rem;">
                <h3><i class="fas fa-project-diagram"></i> Workflow Process</h3>
                <div class="action-grid workflow-steps" id="workflowSteps">
                    <div class="workflow-column">
                        <span style="font-size: 10px; font-weight: bold; color: #00c6ff; text-transform: uppercase;">Plan</span>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <a href="event_center.php" class="action-button"><i class="fas fa-exclamation-circle"></i> <span>Event</span></a>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <a href="investigation_list.html" class="action-button"><i class="fas fa-search"></i> <span>Investigate</span></a>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <a href="assessment_list.php" class="action-button"><i class="fas fa-clipboard-check"></i> <span>Assess</span></a>
                        </div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-chevron-right" style="color: rgba(255,255,255,0.2);"></i></div>
                    <div class="workflow-column">
                        <span style="font-size: 10px; font-weight: bold; color: #50fa7b; text-transform: uppercase;">Do</span>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <a href="task_center.html" class="action-button"><i class="fas fa-tasks"></i> <span>Task</span></a>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <a href="permit_list.php" class="action-button"><i class="fas fa-file-contract"></i> <span>PTW</span></a>
                        </div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-chevron-right" style="color: rgba(255,255,255,0.2);"></i></div>
                    <div class="workflow-column">
                        <span style="font-size: 10px; font-weight: bold; color: #ffb86c; text-transform: uppercase;">Check</span>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <a href="process_analytics.html" class="action-button"><i class="fas fa-chart-line"></i> <span>Monitor</span></a>
                        </div>
                    </div>
                    <div class="workflow-arrow"><i class="fas fa-chevron-right" style="color: rgba(255,255,255,0.2);"></i></div>
                    <div class="workflow-column">
                        <span style="font-size: 10px; font-weight: bold; color: #ff5555; text-transform: uppercase;">Act</span>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <a href="event_list.php?status=Closed" class="action-button"><i class="fas fa-flag-checkered"></i> <span>Close</span></a>
                            <div class="workflow-arrow"><i class="fas fa-arrow-right"></i></div>
                            <a href="event_center.php?status=Effectiveness Review" class="action-button"><i class="fas fa-check-double"></i> <span>Review</span></a>
                        </div>
                    </div>
                    <div class="workflow-arrow workflow-arrow-cycle"><i class="fas fa-redo"></i></div>
                </div>
            </div>

            <div class="quick-actions" style="margin-top: 2rem;">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="action-grid" id="quickActions"></div>
            </div>
        </section>

        <section class="section-group" id="ops">
            <h2 class="section-title">Operations & Mapping</h2>
            <div class="dashboard-grid">
                <a class="action-card" onclick="window.location.href='processmap.html'"><span class="card-tag cat-ops">Operations</span><div class="card-icon">🛠️</div><div class="card-info"><h3>Tree Dropdown</h3><p>Hierarchical process navigation and selection.</p></div></a>
                <a class="action-card" onclick="window.location.href='processflow/orgchart.html'"><span class="card-tag cat-ops">Operations</span><div class="card-icon">📊</div><div class="card-info"><h3>Process Org</h3><p>Organizational flow and structural maps.</p></div></a>
                <a class="action-card" onclick="window.location.href='system_relationship_map.html'"><span class="card-tag cat-ops">Operations</span><div class="card-icon"><i class="fas fa-project-diagram"></i></div><div class="card-info"><h3>Schema Diagram</h3><p>System relationships and data flow architecture.</p></div></a>
                <a class="action-card" onclick="window.location.href='processschema.html'"><span class="card-tag cat-ops">Operations</span><div class="card-icon">🗺️</div><div class="card-info"><h3>Process Map</h3><p>Visual overview of operational sequences.</p></div></a>
                <a class="action-card" onclick="window.location.href='tree10.html'"><span class="card-tag cat-ops">Operations</span><div class="card-icon">📁</div><div class="card-info"><h3>Repo Map</h3><p>Repository structure and file management.</p></div></a>
                <a class="action-card" onclick="window.location.href='PY/run_bms.php'"><span class="card-tag cat-ops">Operations</span><div class="card-icon">⚙️</div><div class="card-info"><h3>BMS</h3><p>Building Management System control portal.</p></div></a>
            </div>
        </section>

        <section class="section-group" id="comp">
            <h2 class="section-title">Safety & Compliance</h2>
            <div class="dashboard-grid">
                <a class="action-card" onclick="window.location.href='dashboard_permit.php'"><span class="card-tag cat-comp">Compliance</span><div class="card-icon">📝</div><div class="card-info"><h3>Permit-to-Work</h3><p>PTW management and authorization portal.</p></div></a>
                <a class="action-card" onclick="window.location.href='permittest.html'"><span class="card-tag cat-comp">Compliance</span><div class="card-icon">📄</div><div class="card-info"><h3>PTW Template</h3><p>Standardized templates for safety permits.</p></div></a>
                <a class="action-card" onclick="window.location.href='KPIEHS_navigation.php'"><span class="card-tag cat-comp">Compliance</span><div class="card-icon">📈</div><div class="card-info"><h3>EHS KPIs</h3><p>Environmental, Health, and Safety metrics dashboards.</p></div></a>
                <a class="action-card" onclick="window.location.href='fishbone.html'"><span class="card-tag cat-comp">Compliance</span><div class="card-icon">🦴</div><div class="card-info"><h3>Fishbone Analysis</h3><p>Root cause analysis and problem solving tools.</p></div></a>
                <a class="action-card" onclick="window.location.href='7ps_registry.php'"><span class="card-tag cat-comp">Compliance</span><div class="card-icon">👥</div><div class="card-info"><h3>7Ps Registry</h3><p>People, Plant, Place, Product, Energy, Purpose & Process.</p></div></a>
                <a class="action-card" onclick="window.open('../sheener/riskassessmenttest/index.html', '_blank')"><span class="card-tag cat-comp">Compliance</span><div class="card-icon"><img src="img/RA.png" alt="RA Logo"></div><div class="card-info"><h3>Risk Assessment</h3><p>Comprehensive RA tools and history.</p></div></a>
            </div>
        </section>

        <section class="section-group" id="ent">
            <h2 class="section-title">Enterprise Portals</h2>
            <div class="dashboard-grid">
                <a class="action-card" onclick="window.open('http://172.21.10.116:96/jde/E1Menu.maf', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1jde.png" alt="JDE Logo"></div><div class="card-info"><h3>JDE Cloud</h3><p>JD Edwards EnterpriseOne Resource Planning.</p></div></a>
                <a class="action-card" onclick="window.open('https://amneal.discus.solutions/cams-login-web/#/', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1Training.png" alt="Training Logo"></div><div class="card-info"><h3>CAMS Training</h3><p>Employee learning management and training portal.</p></div></a>
                <a class="action-card" onclick="window.open('http://qamsprod.amneal.local/caliberQAMS/(S(b0uzq33ifczwrw3xnhhfthmf))/login.aspx', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1caliber.png" alt="Caliber Logo"></div><div class="card-info"><h3>Caliber QAMS</h3><p>Quality Assurance Management System.</p></div></a>
                <a class="action-card" onclick="window.open('https://epiqprod.amneal.com/CIEX064_PROD_EPIQ/', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1epiq.png" alt="EPIQ Logo"></div><div class="card-info"><h3>EPIQ DMS</h3><p>Document Management and version control.</p></div></a>
                <a class="action-card" onclick="window.open('http://172.21.40.30/TMS/TMS/Login/Index?authenticate=True&cs=1118335031', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1clock.png" alt="TMS Logo"></div><div class="card-info"><h3>Time Management</h3><p>TMS clocking and attendance records.</p></div></a>
                <a class="action-card" onclick="window.open('https://amnealpharma.na2.echosign.com/account/homeJS', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/1sign.png" alt="Sign Logo"></div><div class="card-info"><h3>Adobe Sign</h3><p>Electronic signature and document workflow.</p></div></a>
                <a class="action-card" onclick="window.open('https://amneal.platform.leucine.tech/auth/login', '_blank')"><span class="external-badge">External</span><span class="card-tag cat-ent">Enterprise</span><div class="card-icon"><img src="img/leucine.png" alt="Leucine Logo"></div><div class="card-info"><h3>Leucine</h3><p>E-logs platform for digital manufacturing and compliance.</p></div></a>
            </div>
        </section>

        <section class="section-group" id="util">
            <h2 class="section-title">Utilities & Extras</h2>
            <div class="dashboard-grid">
                <a class="action-card" onclick="window.location.href='infographictest.html'"><span class="card-tag cat-util">Utilities</span><div class="card-icon">🎨</div><div class="card-info"><h3>Infographics</h3><p>Visual data representations and assets.</p></div></a>
                <a class="action-card" onclick="window.location.href='../SHEE/SHEEdash.html'"><span class="card-tag cat-util">Utilities</span><div class="card-icon">🌟</div><div class="card-info"><h3>New Dashboard</h3><p>Legacy preview of the SHEE dashboard.</p></div></a>
                <a class="action-card" onclick="window.location.href='../LoadingAnim/index1.html'"><span class="card-tag cat-util">Utilities</span><div class="card-icon">⏳</div><div class="card-info"><h3>Component Lab</h3><p>Testing ground for loading animations and UI.</p></div></a>
                <a class="action-card" onclick="window.open('https://fgas-licensing.ec.europa.eu/fgas/resources/home/', '_blank')"><span class="card-tag cat-util">Utilities</span><div class="card-icon"><img src="img/1HFA.png" alt="F-Gas Logo"></div><div class="card-info"><h3>F-Gas Portal</h3><p>European Union f-gas licensing and resources.</p></div></a>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            try {
                sessionStorage.setItem('user_id', '<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>');
                sessionStorage.setItem('user_role', '<?php echo $role; ?>');
                if (<?php echo isset($_SESSION['user_id']) && $_SESSION['user_id'] == 32 ? 'true' : 'false'; ?>) {
                    sessionStorage.setItem('is_permit_user', 'true');
                } else {
                    sessionStorage.removeItem('is_permit_user');
                }
            } catch(e) { console.error(e); }

            const tabs = document.querySelectorAll('.filter-tab');
            const sections = document.querySelectorAll('.section-group');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const category = tab.getAttribute('data-category');
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    sections.forEach(section => {
                        if (section.id === category) section.classList.add('active');
                        else section.classList.remove('active');
                    });
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            const userRole = '<?php echo $role; ?>';
            const loadQuickActions = () => {
                const roleActions = {
                    'Admin': [
                        { icon: 'fa-chart-line', label: 'Analytics', page: 'process_analytics.html' },
                        { icon: 'fa-users-cog', label: 'User Management', page: 'user_management.php' },
                        { icon: 'fa-file-alt', label: 'Reports', page: 'event_center.php' }
                    ],
                    'Manager': [
                        { icon: 'fa-chart-bar', label: 'Team Reports', page: 'process_analytics.html' },
                        { icon: 'fa-exclamation-triangle', label: 'Risks', page: 'assessment_list.php' }
                    ],
                    'Supervisor': [
                        { icon: 'fa-clipboard-list', label: 'Events', page: 'event_center.php' },
                        { icon: 'fa-file-contract', label: 'Permits', page: 'permit_list.php' },
                        { icon: 'fa-chart-pie', label: 'Analytics', page: 'process_analytics.html' }
                    ],
                    'User': [
                        { icon: 'fa-tasks', label: 'My Tasks', page: 'employee_tasks.php' },
                        { icon: 'fa-file-contract', label: 'My Permits', page: 'permit_list.php' },
                        { icon: 'fa-exclamation-circle', label: 'Report Incident', page: 'event_center.php' },
                        { icon: 'fa-book', label: 'Procedures', page: 'agent.html?context=procedures' }
                    ]
                };
                const actions = roleActions[userRole] || roleActions['User'];
                const container = document.getElementById('quickActions');
                if (container) {
                    container.innerHTML = actions.map(a => 
                        `<a href="${a.page}" class="action-button"><i class="fas ${a.icon}"></i><span>${a.label}</span></a>`
                    ).join('');
                }
            };

            const loadDashboardStats = async () => {
                const container = document.getElementById('dashboardStats');
                if (!container) return;
                try {
                    const response = await fetch('api/data_service.php?action=dashboard_stats');
                    const data = await response.json();
                    if (data.success && data.data) {
                        const stats = data.data;
                        container.innerHTML = `
                            <div class="stat-card"><div class="stat-card-header"><div class="stat-card-title">Active Risks</div><div class="stat-card-icon danger"><i class="fas fa-exclamation-triangle"></i></div></div><div class="stat-card-value">${stats.risks?.active || 0}</div><div class="stat-card-label">${stats.risks?.critical || 0} critical</div></div>
                            <div class="stat-card"><div class="stat-card-header"><div class="stat-card-title">Active Permits</div><div class="stat-card-icon primary"><i class="fas fa-file-contract"></i></div></div><div class="stat-card-value">${stats.permits?.active || 0}</div><div class="stat-card-label">${stats.permits?.overdue || 0} overdue</div></div>
                            <div class="stat-card"><div class="stat-card-header"><div class="stat-card-title">Pending Tasks</div><div class="stat-card-icon warning"><i class="fas fa-tasks"></i></div></div><div class="stat-card-value">${stats.tasks?.pending || 0}</div><div class="stat-card-label">${stats.tasks?.overdue || 0} overdue</div></div>
                            <div class="stat-card"><div class="stat-card-header"><div class="stat-card-title">Recent Events</div><div class="stat-card-icon success"><i class="fas fa-calendar-alt"></i></div></div><div class="stat-card-value">${stats.events?.total || 0}</div><div class="stat-card-label">Last 30 days</div></div>
                        `;
                    }
                } catch (e) { console.error(e); }
            };

            loadQuickActions();
            loadDashboardStats();
        });
    </script>
</body>
</html>

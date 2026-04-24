<?php
/* File: sheener/permit_list.php */
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$page_title = 'Permit Center - Process Management System';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = ['js/searchable_dropdown.js', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js', 'js/vendor/jspdf.umd.min.js', 'js/vendor/qrcode.min.js', 'js/permit_manager.js', 'js/task_manager.js', 'js/date-input-handler.js'];
$additional_stylesheets = ['css/task_center.css', 'css/ui-standard.css', 'css/searchable_dropdown.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
include 'includes/header.php';
// Include the new Permit Creation Flow Modal
include 'includes/modals/permit_creation_flow.php';
// Include the new Email Compose Modal
include 'includes/modals/email_compose_modal.php';
?>

<main class="task-center-container">
    <header class="page-header">
        <h1><i class="fas fa-file-alt"></i> Permit Center</h1>
        <div class="header-actions">
            <input type="search" id="searchInput" placeholder="Search permits..." class="search-input">
        </div>
    </header>

    <div class="bottom-toolbar">
        <div class="view-controls">
            <div class="view-switcher">
                <button class="btn-view active" data-view="kanban"><i class="fas fa-columns"></i> Kanban</button>
                <button class="btn-view" data-view="calendar"><i class="fas fa-calendar"></i> Calendar</button>
                <button class="btn-view" data-view="list"><i class="fas fa-list"></i> List</button>
            </div>
            <div class="filters-bar">
                <select id="filterStatus" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Issued">Issued</option>
                    <option value="Active">Active</option>
                    <option value="Closed">Closed</option>
                    <option value="Expired">Expired</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <select id="filterType" class="filter-select">
                    <option value="">All Types</option>
                    <option value="Hot Work">Hot Work</option>
                    <option value="Cold Work">Cold Work</option>
                    <option value="Clearance">Clearance</option>
                    <option value="Work at Height">Work at Height</option>
                    <option value="Confined Space">Confined Space</option>
                    <option value="Electrical Work">Electrical Work</option>
                    <option value="General Work">General Work</option>
                </select>
                <select id="filterTask" class="filter-select">
                    <option value="">All Tasks</option>
                </select>
                <button class="btn-add" id="btnAddPermit"><i class="fas fa-plus"></i> New Permit</button>
            </div>
        </div>
    </div>

    <div class="tasks-container">
        <div id="permitsKanban" class="tasks-kanban-view active">
            <div class="kanban-columns">
                <div class="kanban-column" data-status="Issued"><div class="kanban-header"><h3>Issued</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-issued"></div></div>
                <div class="kanban-column" data-status="Active"><div class="kanban-header"><h3>Active</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-active"></div></div>
                <div class="kanban-column" data-status="Closed"><div class="kanban-header"><h3>Closed</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-closed"></div></div>
                <div class="kanban-column" data-status="Expired"><div class="kanban-header"><h3>Expired</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-expired"></div></div>
                <div class="kanban-column" data-status="Revoked"><div class="kanban-header"><h3>Revoked</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-revoked"></div></div>
                <div class="kanban-column" data-status="Cancelled"><div class="kanban-header"><h3>Cancelled</h3><span class="kanban-count">0</span></div><div class="kanban-items" id="kanban-cancelled"></div></div>
            </div>
        </div>
        <div id="permitsCalendar" class="tasks-calendar-view"><div id="calendarGrid" class="calendar-grid"></div></div>
        <div id="permitsList" class="tasks-list-view"></div>
    </div>
</main>

<!-- Permit creation is now handled by includes/modals/permit_creation_flow.php -->

<!-- Removed legacy taskRequiredWarningModal as it is handled by permit_creation_flow.php -->

<div id="viewPermitModal" class="modal-overlay hidden"></div>

<style>
    .form-group { margin-bottom: 25px !important; }
    .card-body { padding: 35px !important; }
    /* CONSISTENT FIELD SPACING */
    .form-group {
        margin-bottom: 15px !important; /* Standard base */
    }
    .permit-modal-grid .form-group {
        margin-bottom: 10px !important; /* User requested 10px */
    }
    /* WIDEN THE ENTIRE TASK DROPDOWN FIELD */
    #permit_task_container {
        width: 50% !important; /* Increased to 150px for better visibility */
        position: relative !important;
        z-index: 10 !important;
    }
    #permit_task_container .searchable-dropdown,
    #permit_task_container .dropdown-input-wrapper,
    #permit_task_container .dropdown-input {
        width: 100% !important;
    }
    /* WIDEN TASK DROPDOWN ONLY */
    #DELETE_ME {
        width: calc(100% + 100px) !important;
        max-width: none !important;
    }
    #addPermitModal .modal-content form { display: block !important; width: 100% !important; grid-template-columns: none !important; }
    .permit-modal-grid { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 30px !important; padding: 40px !important; width: 100% !important; box-sizing: border-box !important; }
    .header-entry { grid-column: 1 / -1 !important; }
    #addPermitModal .modal-content { transform: translateY(-25px) !important;  max-width: 1500px !important; width: 95% !important; height: 90vh !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; }
    #addPermitForm { flex: 1 !important; overflow-y: auto !important; width: 100% !important;}
    .fab-save-permit { position: absolute !important; top: 115px !important; right: 40px !important; width: 65px !important; height: 65px !important; background: #3b82f6 !important; border-radius: 50% !important; color: white !important; z-index: 1000 !important; cursor: pointer; display: flex; align-items: center; justify-content: center;}
    .form-card { background: white !important; border-radius: 12px !important; border: 1px solid #e2e8f0 !important; margin-bottom: 20px !important; width: 100% !important; overflow: hidden !important; }
    .card-header { background: #334155 !important; color: white !important; padding: 15px !important; font-weight: 700; display: flex !important; align-items: center !important; gap: 10px !important; }
    .form-grid-3 { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 20px !important; }
    .std-attachment-zone { border: 2px dashed #94a3b8 !important; padding: 40px !important; text-align: center !important; }
    #addPermitModal.modal-overlay { overflow: hidden !important;  background: rgba(15, 23, 42, 0.8) !important; backdrop-filter: blur(8px) !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnAddPermit = document.getElementById('btnAddPermit');
        if (btnAddPermit) {
            btnAddPermit.onclick = function (e) {
                e.preventDefault();
                if (typeof openTaskSearchModal === 'function') {
                    openTaskSearchModal();
                } else {
                    console.error("openTaskSearchModal not found");
                }
            };
        }
    });

    function closeAllAddPermitModals() {
        // Handle any lingering closure logic if needed
        const modals = ['taskSearchModal', 'createTaskFlowModal', 'createPermitFlowModal'];
        modals.forEach(id => {
            const m = document.getElementById(id);
            if(m) m.classList.add('hidden');
        });
    }
</script>
<script src="js/permit_flow.js"></script>
<?php include 'includes/footer.php'; ?>









<?php
/* File: sheener/7ps_registry.php */

/**
 * 7Ps Registry - Process Management System
 */
session_start();
$page_title = '7Ps Registry - Process Management System';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

$additional_stylesheets = [
    'css/7ps_registry.css',
    'css/ui-standard.css'
];

$additional_scripts = [
    'js/7ps_manager.js',
    'js/7ps_registry_modals.js'
];

include 'includes/header.php';
?>

<main class="seven-ps-registry-container">
    <header class="page-header">
        <h1><i class="fas fa-users"></i> 7Ps Registry</h1>
        <div class="header-actions">
            <input type="search" id="searchInput" placeholder="Search..." class="search-input">
        </div>
    </header>

    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-btn active" data-tab="people"><i class="fas fa-user"></i> People</button>
            <button class="tab-btn" data-tab="plant"><i class="fas fa-industry"></i> Plant</button>
            <button class="tab-btn" data-tab="place"><i class="fas fa-map-marker-alt"></i> Place</button>
            <button class="tab-btn" data-tab="product"><i class="fas fa-box"></i> Product</button>
            <button class="tab-btn" data-tab="energy"><i class="fas fa-bolt"></i> Energy</button>
            <button class="tab-btn" data-tab="purpose"><i class="fas fa-file-alt"></i> Purpose</button>
            <button class="tab-btn" data-tab="process"><i class="fas fa-cogs"></i> Process</button>
        </div>

        <div class="tab-content active" id="tab-people">
            <div class="content-header">
                <h2>People Registry</h2>
                <button class="btn-add" onclick="openAddModal('people')"><i class="fas fa-plus"></i> Add
                    Person</button>
            </div>
            <div id="peopleList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-plant">
            <div class="content-header">
                <h2>Plant (Equipment) Registry</h2>
                <button class="btn-add" onclick="openAddModal('equipment')"><i class="fas fa-plus"></i> Add
                    Equipment</button>
            </div>
            <div id="equipmentList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-place">
            <div class="content-header">
                <h2>Place (Areas) Registry</h2>
                <button class="btn-add" onclick="openAddModal('areas')"><i class="fas fa-plus"></i> Add
                    Area</button>
            </div>
            <div id="areasList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-product">
            <div class="content-header">
                <h2>Product (Materials) Registry</h2>
                <button class="btn-add" onclick="openAddModal('materials')"><i class="fas fa-plus"></i> Add
                    Material</button>
            </div>
            <div id="materialsList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-energy">
            <div class="content-header">
                <h2>Energy Registry</h2>
                <button class="btn-add" onclick="openAddModal('energy')"><i class="fas fa-plus"></i> Add
                    Energy</button>
            </div>
            <div id="energyList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-purpose">
            <div class="content-header">
                <h2>Purpose (Documents/SOPs) Registry</h2>
                <button class="btn-add" onclick="openAddModal('documents')"><i class="fas fa-plus"></i> Add
                    Document</button>
            </div>
            <div id="documentsList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

        <div class="tab-content" id="tab-process">
            <div class="content-header">
                <h2>Process Registry</h2>
                <div class="header-actions-group">
                    <button class="btn-add" onclick="openAddModal('process')"><i class="fas fa-plus"></i> Add
                        Process</button>
                    <button class="btn-add" onclick="window.location.href='process_map_diagram.html'"
                        style="background: #27ae60;"><i class="fas fa-project-diagram"></i> Process Map</button>
                </div>
            </div>
            <div id="processList" class="items-list">
                <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</main>

<?php 
include 'includes/7ps_modals_html.php';
include 'includes/footer.php'; 
?>

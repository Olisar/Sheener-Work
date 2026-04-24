<?php
/* File: sheener/includes/header.php */

/**
 * Standard Header Include
 * Provides consistent header, navbar, topbar, and optional AI Navigator
 * 
 * Usage:
 * <?php 
 *   $page_title = 'Page Title';
 *   $use_ai_navigator = true;
 *   $user_role = $_SESSION['role'] ?? 'User';
 *   $user_id = $_SESSION['user_id'] ?? '';
 *   $user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
 *   include 'includes/header.php';
 * ?>
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

    <?php if (isset($page_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php endif; ?>
    <?php if (isset($page_keywords)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <?php endif; ?>
    <?php if (isset($page_author)): ?>
    <meta name="author" content="<?php echo htmlspecialchars($page_author); ?>">
    <?php endif; ?>
    <title><?php echo htmlspecialchars($page_title ?? 'SHEEner'); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/modal.css">
    <?php if (isset($use_ai_navigator) && $use_ai_navigator): ?>
    <link rel="stylesheet" href="css/ai-navigator.css">
    <?php endif; ?>
    <link rel="stylesheet" href="css/date-picker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="img/favicon/faviconAY.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <script src="js/navbar.js" defer></script>
    <script src="js/topbar.js" defer></script>
    <script src="js/modal.js" defer></script>
    <script src="js/date-utils.js" defer></script>
    <script src="js/date-input-handler.js" defer></script>
    <script src="js/date-picker.js" defer></script>
    <?php if (isset($use_ai_navigator) && $use_ai_navigator): ?>
    <script src="js/ai-navigator.js" defer></script>
    <?php endif; ?>
    <?php if (isset($additional_stylesheets)): ?>
        <?php foreach ($additional_stylesheets as $sheet): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($sheet); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
    <script src="<?php echo htmlspecialchars($script); ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Premium Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay show">
        <div class="loading-spinner-container">
            <div class="loading-spinner-icon">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div class="loading-spinner-text" id="loadingText">Loading System</div>
            <div class="loading-spinner-subtext" id="loadingSubtext">Preparing your workspace...</div>
            <div class="loading-progress-bar">
                <div class="loading-progress-bar-fill"></div>
            </div>
        </div>
    </div>

    <div id="topbar"></div>
    <div id="navbar"></div>
    <?php if (isset($use_ai_navigator) && $use_ai_navigator): ?>
    <!-- AI Navigator Sidebar -->
    <div id="ai-navigator-container" 
         data-role="<?php echo htmlspecialchars($user_role ?? 'User'); ?>"
         data-user-id="<?php echo htmlspecialchars($user_id ?? ''); ?>"
         data-user-name="<?php echo htmlspecialchars($user_name ?? 'User'); ?>">
    </div>
    <?php endif; ?>

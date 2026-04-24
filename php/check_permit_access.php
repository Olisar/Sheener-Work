<?php
/* File: sheener/php/check_permit_access.php */

/**
 * Access control for Permit user (people_id = 32)
 * Include this at the top of any page that should be accessible to Permit user
 * 
 * Usage: require_once 'php/check_permit_access.php';
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// If user is Permit user (32), allow access to permit pages only
if ($_SESSION['user_id'] == 32) {
    $allowed_pages = [
        'dashboard_permit.php',
        'permit1.php',
        'permit_list.php',
        'permit_form.php',
        'permitlist1.php',
        'permit_list0.php',
        'permit_list1.php',
        'permitlist11.php'
    ];
    
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if (!in_array($current_page, $allowed_pages)) {
        // Check if it's an API endpoint for permits
        $request_uri = $_SERVER['REQUEST_URI'];
        $is_permit_api = (
            strpos($request_uri, 'get_all_permits.php') !== false ||
            strpos($request_uri, 'get_permit.php') !== false ||
            strpos($request_uri, 'create_permit.php') !== false ||
            strpos($request_uri, 'add_permit.php') !== false ||
            strpos($request_uri, 'update_permit.php') !== false ||
            strpos($request_uri, 'get_permits_by_task.php') !== false ||
            strpos($request_uri, 'delete_permit.php') !== false
        );
        
        if (!$is_permit_api) {
            header("Location: ../dashboard_permit.php");
            exit();
        }
    }
}
?>


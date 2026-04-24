<?php
/* File: sheener/tmp_test_php_error.php */

// /tmp/test_php_error.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'd:/xampp/htdocs/sheener/php/upload_vendor_attachment.php';
} catch (Throwable $t) {
    echo "\n\nCAUGHT THROWABLE: " . $t->getMessage();
}
?>

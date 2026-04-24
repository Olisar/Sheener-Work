<?php
/* File: sheener/php/fix_upload_permissions.php */

/**
 * Helper script to check and fix upload directory permissions on Windows
 * Run this from command line: php fix_upload_permissions.php
 */

$webRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
$uploadsDir = $webRoot . DIRECTORY_SEPARATOR . 'uploads';
$docsDir = $uploadsDir . DIRECTORY_SEPARATOR . 'docs';

echo "Checking upload directories...\n";
echo "Web root: $webRoot\n";
echo "Uploads dir: $uploadsDir\n";
echo "Docs dir: $docsDir\n\n";

// Check/create directories
$dirs = [$uploadsDir, $docsDir];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        echo "Creating directory: $dir\n";
        if (@mkdir($dir, 0777, true)) {
            echo "  ✓ Created successfully\n";
        } else {
            echo "  ✗ Failed to create\n";
        }
    } else {
        echo "Directory exists: $dir\n";
    }
    
    // Test writability
    $testFile = $dir . DIRECTORY_SEPARATOR . '.write_test_' . time() . '.tmp';
    $testWrite = @file_put_contents($testFile, 'test');
    if ($testWrite !== false) {
        @unlink($testFile);
        echo "  ✓ Writable\n";
    } else {
        echo "  ✗ NOT writable\n";
        echo "  → Please fix permissions manually:\n";
        echo "    1. Right-click the folder: $dir\n";
        echo "    2. Properties > Security tab\n";
        echo "    3. Click 'Edit' and ensure the web server user (usually 'IIS_IUSRS' or 'Everyone') has 'Write' permission\n";
        echo "    4. Apply to all subfolders and files\n\n";
    }
}

// Check specific document directories
echo "\nChecking document-specific directories...\n";
if (is_dir($docsDir)) {
    $dirs = glob($docsDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $testFile = $dir . DIRECTORY_SEPARATOR . '.write_test_' . time() . '.tmp';
        $testWrite = @file_put_contents($testFile, 'test');
        if ($testWrite !== false) {
            @unlink($testFile);
            echo "  ✓ " . basename($dir) . " is writable\n";
        } else {
            echo "  ✗ " . basename($dir) . " is NOT writable\n";
        }
    }
}

echo "\nDone.\n";


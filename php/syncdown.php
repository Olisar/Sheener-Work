<?php
/* File: sheener/php/syncdown.php */

/**
 * Sync down script - Copies only NEW or UPDATED files
 * from D:\sheener  →  C:\xampp0\htdocs\sheener
 * SECURITY WARNING: Only use in local development environment
 * 
 * Sheener/php/syncdown.php
 */

// JSON header (for fetch/AJAX)
header('Content-Type: application/json');

// Paths
$source      = 'D:/sheener';     // Backup on D:
$destination = dirname(__DIR__); // C:\xampp0\htdocs\sheener

// Security: Only allow execution from localhost
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. This script can only be run from localhost.'
    ]);
    exit;
}

function incrementalSyncDown($src, $dst) {
    if (!is_dir($src)) {
        throw new Exception("Source directory not found: $src");
    }

    $dstParent = dirname($dst);
    if (!is_dir($dstParent)) {
        throw new Exception("Destination parent directory not found: $dstParent");
    }

    if (!is_dir($dst) && !mkdir($dst, 0777, true)) {
        throw new Exception("Cannot create destination directory: $dst - Check permissions.");
    }

    $filesCopied = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = $iterator->getSubPathName();
        $destPath     = $dst . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                if (!mkdir($destPath, 0777, true)) {
                    throw new Exception("Failed to create directory: $destPath");
                }
            }
            continue;
        }

        $srcPath = $item->getPathname();
        $srcTime = filemtime($srcPath);
        if ($srcTime === false) {
            continue;
        }

        $destExists = file_exists($destPath);
        $destTime   = $destExists ? filemtime($destPath) : 0;
        if ($destExists && $destTime === false) {
            $destTime = 0;
        }

// Copy only if: destination EXISTS and source (D:) is NEWER
// -> do NOT create new files on C:, only update existing ones
if ($destExists && $srcTime > $destTime) {
    if (!copy($srcPath, $destPath)) {
        throw new Exception("Failed to copy file: $srcPath to $destPath");
    }
    @touch($destPath, $srcTime);
    $filesCopied++;
}

    }

    return $filesCopied;
}

try {
    $filesCopied = incrementalSyncDown($source, $destination);

    $message = "✅ Sync down completed!\n\n"
             . "Files copied/updated: $filesCopied\n\n"
             . "From: $source\nTo: $destination";

    echo json_encode([
        'success'     => true,
        'message'     => $message,
        'filesCopied' => $filesCopied
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "❌ Sync down failed: " . $e->getMessage()
    ]);
}
?>

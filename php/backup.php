<?php
/* File: sheener/php/backup.php */

/**
 * Backup script - Copies only NEW or UPDATED files from source to destination
 * Automatically detects project folder location
 * C:\xampp0\htdocs\sheener  →  D:\sheener (default)
 * C:\xampp0\htdocs\sheener  →  C:\Users\ogras\Documents\dumps\sheener_backup_TIMESTAMP (Local dump)
 * SECURITY WARNING: Only use in local development environment
 * Sheener/php/backup.php
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, but log them
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Only output if headers haven't been sent and no output has been sent
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
            ]);
        }
    }
});

// JSON header (for fetch/AJAX)
header('Content-Type: application/json');

try {
    // Configuration - dynamically detect source path
    $source = dirname(__DIR__); // Automatically detects the project folder location
    
    // Determine destination based on source path
    // If source is on C: drive, use D: drive for backup, otherwise use same drive
    $sourceDrive = substr($source, 0, 2);
    if (strtoupper($sourceDrive) === 'C:') {
        $destination = 'D:/sheener';
    } else {
        // Use same drive as source, but in a backup folder
        $destination = $sourceDrive . '/sheener_backup';
    }

    // Security: Allow execution from localhost and local network
    // Check both REMOTE_ADDR and HTTP_HOST
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    
    // Check for localhost
    $isLocalhost = in_array($remoteAddr, ['127.0.0.1', '::1', 'localhost']) 
                   || strpos($httpHost, 'localhost') !== false
                   || strpos($httpHost, '127.0.0.1') !== false;
    
    // Check for local network IPs (private IP ranges)
    $isLocalNetwork = false;
    if (!empty($remoteAddr)) {
        // IPv4 private ranges: 10.x.x.x, 172.16-31.x.x, 192.168.x.x
        if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $remoteAddr)) {
            $isLocalNetwork = true;
        }
        // IPv6 local: fe80::, fc00::, etc.
        if (strpos($remoteAddr, 'fe80:') === 0 || strpos($remoteAddr, 'fc00:') === 0) {
            $isLocalNetwork = true;
        }
    }
    
    // Also check HTTP_HOST for IP addresses
    if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $httpHost)) {
        $isLocalNetwork = true;
    }
    
    if (!$isLocalhost && !$isLocalNetwork) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. This script can only be run from localhost or local network.',
            'debug' => ['remote_addr' => $remoteAddr, 'http_host' => $httpHost]
        ]);
        exit;
    }

    // Check if this is a Special backup request (formerly USB)
    $isSpecialBackup = false;
    $timestamp = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        if ($rawInput === false) {
            throw new Exception('Failed to read request input');
        }
        
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input: ' . json_last_error_msg());
        }
        
        if (isset($input['target']) && $input['target'] === 'usb') {
            $isSpecialBackup = true;
            $timestamp = isset($input['timestamp']) ? $input['timestamp'] : date('Y-m-d_H-i-s');
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Initialization error: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Detect USB drive by checking common drive letters
 * @deprecated Redirected to local dumps
 */
function detectUSBDrive() {
    return null;
}


function incrementalCopy($src, $dst) {
    if (!is_dir($src)) {
        throw new Exception("Source directory not found: $src");
    }

    $dstParent = dirname($dst);
    if (!is_dir($dstParent)) {
        if (!mkdir($dstParent, 0777, true)) {
            throw new Exception("Destination parent directory not found and could not be created: $dstParent - Please check permissions.");
        }
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
                mkdir($destPath, 0777, true);
            }
            continue;
        }

        // File logic
        $srcPath = $item->getPathname();

        $srcTime = filemtime($srcPath);
        if ($srcTime === false) {
            // If we can't read source time, skip to be safe
            continue;
        }

        $destExists = file_exists($destPath);
        $destTime   = $destExists ? filemtime($destPath) : 0;
        if ($destExists && $destTime === false) {
            $destTime = 0;
        }

        // Copy only if: dest missing OR source newer
        if (!$destExists || $srcTime > $destTime) {
            if (!copy($srcPath, $destPath)) {
                throw new Exception("Failed to copy file: $srcPath to $destPath");
            }
            // Ensure destination has same timestamp as source
            @touch($destPath, $srcTime);
            $filesCopied++;
        }
    }

    return $filesCopied;
}

try {
    // Handle Special backup (to local dumps)
    if ($isSpecialBackup) {
        // Validate that source path exists and is accessible
        if (!is_dir($source)) {
            throw new Exception("Source directory not found: $source\n\nPlease ensure the project folder is accessible.");
        }
        
        // Log the detected source for debugging (optional)
        error_log("Backup source detected: $source");
        
        $targetDir = 'C:/Users/ogras/Documents/dumps';
        
        // Ensure target directory exists
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                throw new Exception("Failed to create target directory: $targetDir\n\nPlease ensure Apache has write permissions to this path.");
            }
        }
        
        // Create timestamped backup folder in the dumps directory
        $backupFolderName = 'sheener_backup_' . $timestamp;
        $destination = $targetDir . '/' . $backupFolderName;
        
        // Perform backup into the new timestamped folder
        $filesCopied = incrementalCopy($source, $destination);
        
        $message = "✅ Backup completed!\n\n"
                 . "Files copied: $filesCopied\n\n"
                 . "From: $source\n"
                 . "To: $destination\n\n"
                 . "Timestamp: $timestamp";
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'filesCopied' => $filesCopied,
            'destination' => $destination,
            'timestamp' => $timestamp,
            'source' => $source  // Include source in response for debugging
        ]);
    } else {
        // Default backup to D: drive (incremental)
        $filesCopied = incrementalCopy($source, $destination);
        $message = "✅ Incremental backup completed!\n\n"
                 . "Files copied/updated: $filesCopied\n\n"
                 . "From: $source\nTo: $destination";

        echo json_encode([
            'success' => true,
            'message' => $message,
            'filesCopied' => $filesCopied,
            'source' => $source,  // Include source in response for debugging
            'destination' => $destination
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "❌ Backup failed: " . $e->getMessage()
    ]);
}
?>

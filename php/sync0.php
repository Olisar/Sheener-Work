<?php
/* File: sheener/php/sync0.php */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

set_time_limit(300); 
ini_set('memory_limit', '256M');

// Configuration
$usbVolumeLabel = "Philips"; 
$logFile = __DIR__ . '/sync_log_' . date('Y-m-d') . '.txt';
$isDryRun = false; // Default

// --- Helper Functions ---

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function getDriveLetterByLabel($label) {
    $cmd = 'wmic logicaldisk get caption,volumename';
    $output = [];
    exec($cmd, $output);

    foreach ($output as $line) {
        if (stripos($line, $label) !== false) {
            if (preg_match('/^([A-Z]:)/', $line, $matches)) {
                return $matches[1];
            }
        }
    }
    return null;
}

function ensureDirectory($path) {
    global $isDryRun;
    if ($isDryRun) return; // Don't create folders in dry run

    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function copyFile($source, $dest) {
    global $isDryRun;

    if ($isDryRun) {
        logMessage("[DRY RUN] Would copy: " . basename($source) . " -> " . $dest);
        return true; // Pretend it worked
    }

    if (!file_exists($source)) return false;
    
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    if (file_exists($dest) && !is_writable($dest)) {
        chmod($dest, 0777); 
    }
    
    return @copy($source, $dest);
}

function getAllFiles($dir) {
    $files = [];
    $dir = rtrim($dir, '\\');
    if (!is_dir($dir)) return $files;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($dir) + 1);
                $files[$relativePath] = [
                    'mtime' => $file->getMTime(),
                    'size'  => $file->getSize()
                ];
            }
        }
    } catch (Exception $e) {
        logMessage("Error scanning $dir: " . $e->getMessage());
    }
    return $files;
}

// --- Main Logic ---

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$scenarioId = $input['scenario'] ?? '1';
$isDryRun = $input['dry_run'] ?? false; // Check if simulation mode is on

// 1. Detect USB
$usbDrive = getDriveLetterByLabel($usbVolumeLabel);

if (!$usbDrive) {
    echo json_encode(['success' => false, 'message' => "USB '$usbVolumeLabel' not found."]);
    exit;
}

// 2. Define Paths
$paths = [];
if ($scenarioId == '1') {
    $localPath = is_dir('C:\\xampp0\\htdocs\\sheener') ? 'C:\\xampp0\\htdocs\\sheener' : 'C:\\xampp\\htdocs\\sheener';
    $paths = [
        'source' => $localPath,
        'target' => $usbDrive . '\\sheener_backup'
    ];
} elseif ($scenarioId == '2') {
    $paths = [
        'source' => 'C:\\xampp\\htdocs\\sheener',
        'target' => $usbDrive . '\\sheener_backup'
    ];
} else {
    $paths = ['source' => $input['source'] ?? '', 'target' => $input['target'] ?? ''];
}

$dir1 = rtrim($paths['source'], '\\');
$dir2 = rtrim($paths['target'], '\\');

if ($isDryRun) {
    logMessage("--- STARTING DRY RUN (SIMULATION) ---");
} else {
    logMessage("--- STARTING REAL SYNC ---");
}
logMessage("Source: $dir1");
logMessage("Target: $dir2");

if (!is_dir($dir1)) {
    echo json_encode(['success' => false, 'message' => "Source folder not found: $dir1"]);
    exit;
}

// --- Execution ---

$stats = ['synced' => 0, 'errors' => [], 'skipped' => 0];
$excludeDirs = ['.git', 'node_modules', 'vendor'];

try {
    ensureDirectory($dir2);

    $files1 = getAllFiles($dir1);
    // If target doesn't exist yet, files2 is empty
    $files2 = is_dir($dir2) ? getAllFiles($dir2) : [];
    
    $allPaths = array_unique(array_merge(array_keys($files1), array_keys($files2)));

    foreach ($allPaths as $relativePath) {
        foreach ($excludeDirs as $ex) {
            if (strpos($relativePath, $ex) === 0) continue 2; 
        }

        $file1 = $dir1 . '\\' . $relativePath;
        $file2 = $dir2 . '\\' . $relativePath;
        
        $exists1 = isset($files1[$relativePath]);
        $exists2 = isset($files2[$relativePath]);

        if ($exists1 && $exists2) {
            $mtime1 = $files1[$relativePath]['mtime'];
            $mtime2 = $files2[$relativePath]['mtime'];
            
            if (abs($mtime1 - $mtime2) > 2) {
                if ($mtime1 > $mtime2) {
                    copyFile($file1, $file2) ? $stats['synced']++ : $stats['errors'][] = "Failed copy -> USB: $relativePath";
                } else {
                    copyFile($file2, $file1) ? $stats['synced']++ : $stats['errors'][] = "Failed copy <- USB: $relativePath";
                }
            } else {
                $stats['skipped']++;
            }
        } elseif ($exists1 && !$exists2) {
            copyFile($file1, $file2) ? $stats['synced']++ : $stats['errors'][] = "Failed create -> USB: $relativePath";
        } elseif (!$exists1 && $exists2) {
            copyFile($file2, $file1) ? $stats['synced']++ : $stats['errors'][] = "Failed create <- PC: $relativePath";
        }
    }

    $msg = $isDryRun 
        ? "DRY RUN Complete. Would sync: {$stats['synced']} files." 
        : "Sync Complete. Synced: {$stats['synced']}, Skipped: {$stats['skipped']}";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'details' => $stats,
        'is_dry_run' => $isDryRun
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

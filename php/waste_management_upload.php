<?php
/* File: sheener/php/waste_management_upload.php */

/**
 * Waste Management System - Excel Upload Handler
 * Handles file uploads and triggers Python import script
 */

session_start();
require_once 'database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['people_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get form data
    $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : null;
    $user_id = $_SESSION['people_id'];
    
    // Validate vendor if provided
    if ($vendor_id) {
        $stmt = $pdo->prepare("SELECT vendor_id FROM waste_vendors WHERE vendor_id = ?");
        $stmt->execute([$vendor_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Invalid vendor ID']);
            exit;
        }
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['excel_file'];
    
    // Validate file type
    $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, ['xls', 'xlsx'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only Excel files (.xls, .xlsx) are allowed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/waste_management/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid('waste_import_', true) . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Get absolute path for Python script
    $absolute_path = realpath($file_path);
    
    // Execute Python import script
    $python_script = __DIR__ . '/../PY/waste_excel_importer.py';
    $command = escapeshellcmd('python') . ' ' . escapeshellarg($python_script) . ' ' . 
               escapeshellarg($absolute_path);
    
    if ($vendor_id) {
        $command .= ' ' . escapeshellarg($vendor_id);
    }
    
    if ($user_id) {
        $command .= ' ' . escapeshellarg($user_id);
    }
    
    // Execute command and capture output
    $output = [];
    $return_var = 0;
    exec($command . ' 2>&1', $output, $return_var);
    
    // Parse output to get import results
    $import_result = [
        'success' => false,
        'rows_imported' => 0,
        'rows_failed' => 0,
        'errors' => []
    ];
    
    // Try to parse JSON output if available
    $output_str = implode("\n", $output);
    if (preg_match('/\{.*\}/s', $output_str, $matches)) {
        $parsed = json_decode($matches[0], true);
        if ($parsed) {
            $import_result = $parsed;
        }
    } else {
        // Parse text output
        foreach ($output as $line) {
            if (preg_match('/Rows imported:\s*(\d+)/i', $line, $m)) {
                $import_result['rows_imported'] = intval($m[1]);
            }
            if (preg_match('/Rows failed:\s*(\d+)/i', $line, $m)) {
                $import_result['rows_failed'] = intval($m[1]);
            }
            if (stripos($line, 'successfully') !== false || stripos($line, 'completed') !== false) {
                $import_result['success'] = true;
            }
            if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
                $import_result['errors'][] = $line;
            }
        }
    }
    
    // If we couldn't determine success from output, check return code
    if ($return_var === 0 && $import_result['rows_imported'] > 0) {
        $import_result['success'] = true;
    }
    
    // Return result
    $response = [
        'success' => $import_result['success'],
        'filename' => $filename,
        'rows_imported' => $import_result['rows_imported'],
        'rows_failed' => $import_result['rows_failed'],
        'message' => $import_result['success'] 
            ? "Successfully imported {$import_result['rows_imported']} rows" 
            : "Import completed with {$import_result['rows_failed']} failed rows"
    ];
    
    if (!empty($import_result['errors'])) {
        $response['errors'] = array_slice($import_result['errors'], 0, 10);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>


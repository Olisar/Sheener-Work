<?php
/* File: sheener/php/waste_management_reports.php */

/**
 * Waste Management System - Report Generation API
 * Generates reports via Python script and returns JSON or Excel
 */

session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['people_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = $_SESSION['people_id'];
    
    if ($method === 'GET') {
        // Get report list or generate report
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            // List available reports
            $stmt = $pdo->prepare("
                SELECT 
                    wr.*,
                    p.FirstName,
                    p.LastName
                FROM waste_reports wr
                LEFT JOIN people p ON wr.generated_by = p.people_id
                ORDER BY wr.generated_at DESC
                LIMIT 50
            ");
            $stmt->execute();
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'reports' => $reports]);
            
        } elseif ($action === 'generate') {
            // Generate new report
            $report_type = $_GET['type'] ?? 'summary';
            $start_date = $_GET['start_date'] ?? null;
            $end_date = $_GET['end_date'] ?? null;
            $vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : null;
            $site_id = isset($_GET['site_id']) ? intval($_GET['site_id']) : null;
            $format = $_GET['format'] ?? 'json'; // json or excel
            
            // Validate report type
            $valid_types = ['summary', 'vendor', 'compliance'];
            if (!in_array($report_type, $valid_types)) {
                echo json_encode(['success' => false, 'error' => 'Invalid report type']);
                exit;
            }
            
            // Validate vendor for vendor report
            if ($report_type === 'vendor' && !$vendor_id) {
                echo json_encode(['success' => false, 'error' => 'Vendor ID required for vendor report']);
                exit;
            }
            
            // Prepare Python command
            $python_script = __DIR__ . '/../PY/waste_report_generator.py';
            $command = escapeshellcmd('python') . ' ' . escapeshellarg($python_script) . ' ' . 
                       escapeshellarg($report_type);
            
            if ($start_date) {
                $command .= ' ' . escapeshellarg($start_date);
            }
            if ($end_date) {
                $command .= ' ' . escapeshellarg($end_date);
            }
            if ($vendor_id) {
                $command .= ' ' . escapeshellarg($vendor_id);
            }
            
            // Generate output path if Excel format
            $output_path = null;
            if ($format === 'excel') {
                $reports_dir = __DIR__ . '/../reports/waste_management/';
                if (!is_dir($reports_dir)) {
                    mkdir($reports_dir, 0755, true);
                }
                $filename = 'waste_report_' . $report_type . '_' . date('YmdHis') . '.xlsx';
                $output_path = $reports_dir . $filename;
                $command .= ' ' . escapeshellarg($output_path);
            }
            
            // Execute Python script
            $output = [];
            $return_var = 0;
            exec($command . ' 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Report generation failed',
                    'details' => implode("\n", $output)
                ]);
                exit;
            }
            
            if ($format === 'excel' && file_exists($output_path)) {
                // Return Excel file info
                $relative_path = '/reports/waste_management/' . $filename;
                echo json_encode([
                    'success' => true,
                    'format' => 'excel',
                    'file_path' => $relative_path,
                    'filename' => $filename,
                    'message' => 'Report generated successfully'
                ]);
            } else {
                // Return JSON data (would need to modify Python script to output JSON)
                echo json_encode([
                    'success' => true,
                    'format' => 'json',
                    'message' => 'Report generated successfully',
                    'output' => implode("\n", $output)
                ]);
            }
            
        } elseif ($action === 'download') {
            // Download report file
            $report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : null;
            
            if (!$report_id) {
                echo json_encode(['success' => false, 'error' => 'Report ID required']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT report_file_path FROM waste_reports WHERE report_id = ?");
            $stmt->execute([$report_id]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$report || !$report['report_file_path']) {
                echo json_encode(['success' => false, 'error' => 'Report file not found']);
                exit;
            }
            
            $file_path = __DIR__ . '/..' . $report['report_file_path'];
            
            if (!file_exists($file_path)) {
                echo json_encode(['success' => false, 'error' => 'Report file does not exist']);
                exit;
            }
            
            // Return file path for download
            echo json_encode([
                'success' => true,
                'file_path' => $report['report_file_path'],
                'filename' => basename($file_path)
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Generate report with POST data (for complex parameters)
        $data = json_decode(file_get_contents('php://input'), true);
        
        $report_type = $data['type'] ?? 'summary';
        $start_date = $data['start_date'] ?? null;
        $end_date = $data['end_date'] ?? null;
        $vendor_id = $data['vendor_id'] ?? null;
        $site_id = $data['site_id'] ?? null;
        $format = $data['format'] ?? 'json';
        
        // Similar logic as GET request
        // ... (implement similar to GET action === 'generate')
        
        echo json_encode(['success' => false, 'error' => 'POST method not fully implemented']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>


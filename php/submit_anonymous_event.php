<?php
/* File: sheener/php/submit_anonymous_event.php */

/**
 * Anonymous Event Submission Endpoint
 * Allows people without credentials to report events
 * Generates PDF report and emails to all people with email addresses
 */

header('Content-Type: application/json');
require_once 'database.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "error" => "This endpoint only accepts POST requests."
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get form data
    $reporter_name = $_POST['reporter_name'] ?? '';
    $reporter_email = $_POST['reporter_email'] ?? '';
    $primaryCategory = $_POST['primaryCategory'] ?? '';
    $secondaryCategory = $_POST['secondaryCategory'] ?? '';
    $description = $_POST['description'] ?? '';
    $eventDate = $_POST['eventDate'] ?? date('Y-m-d H:i:s');
    $location = $_POST['location'] ?? '';
    $location_id = $_POST['location_id'] ?? null;

    // Validate required fields
    if (empty($reporter_name) || empty($primaryCategory) || empty($secondaryCategory) || empty($description) || empty($location)) {
        echo json_encode([
            "success" => false,
            "error" => "Please fill in all required fields including location."
        ]);
        exit;
    }

    // Generate unique event ID
    $eventId = 'ANON-' . date('YmdHis') . '-' . substr(md5(uniqid(rand(), true)), 0, 8);
    
    // Prepare event data for PDF
    $eventData = [
        'event_id' => $eventId,
        'reporter_name' => $reporter_name,
        'reporter_email' => $reporter_email,
        'primary_category' => $primaryCategory,
        'secondary_category' => $secondaryCategory,
        'description' => $description,
        'event_date' => $eventDate,
        'location' => $location,
        'location_id' => $location_id,
        'submitted_date' => date('Y-m-d H:i:s')
    ];

    // Handle file uploads
    $attachments = [];
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploadDir = 'uploads/anonymous_events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileCount = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $fileCount && $i < 10; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $fileSize = $_FILES['attachments']['size'][$i];
                if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                    continue;
                }

                $fileName = $eventId . '_' . basename($_FILES['attachments']['name'][$i]);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $filePath)) {
                    $attachments[] = [
                        'name' => $_FILES['attachments']['name'][$i],
                        'path' => $filePath,
                        'size' => $fileSize
                    ];
                }
            }
        }
    }

    // Generate PDF report
    $pdfPath = generateEventPDF($eventData, $attachments);
    
    // Save event to database first (before email sending)
    // Map primary category to event_type enum
    $eventTypeMap = [
        'Audit' => 'NonCompliance',
        'Near Miss' => 'Adverse Event',
        'Accident' => 'Adverse Event',
        'Good Catch' => 'OFI',
        'Opportunity for Improvement' => 'OFI'
    ];
    $dbEventType = $eventTypeMap[$primaryCategory] ?? 'OFI';
    
    // Get or create anonymous system user for anonymous reports
    // Check if an "Anonymous Reporter" user exists
    $checkAnonymousQuery = "SELECT people_id FROM people WHERE FirstName = 'Anonymous' AND LastName = 'Reporter' LIMIT 1";
    $checkStmt = $pdo->query($checkAnonymousQuery);
    $anonymousUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($anonymousUser) {
        $systemUserId = $anonymousUser['people_id'];
    } else {
        // Create anonymous reporter user if it doesn't exist
        $createAnonymousQuery = "INSERT INTO people (FirstName, LastName, Email, Position, IsActive) 
                                VALUES ('Anonymous', 'Reporter', 'anonymous@system.local', 'System User', 0)";
        $pdo->exec($createAnonymousQuery);
        $systemUserId = $pdo->lastInsertId();
    }
    
    // Save to events table
    $insertQuery = "INSERT INTO events (
        event_type,
        description,
        reported_by,
        status,
        event_subcategory
    ) VALUES (
        :event_type,
        :description,
        :reported_by,
        'Open',
        :event_subcategory
    )";
    
    $fullDescription = "Anonymous Report\n";
    $fullDescription .= "Reporter: " . $reporter_name . "\n";
    if ($reporter_email) {
        $fullDescription .= "Reporter Email: " . $reporter_email . "\n";
    }
    $fullDescription .= "Primary Category: " . $primaryCategory . "\n";
    $fullDescription .= "Secondary Category: " . $secondaryCategory . "\n";
    $fullDescription .= "Event Date: " . $eventDate . "\n";
    $fullDescription .= "Location: " . $location . "\n\n";
    $fullDescription .= "Description:\n" . $description;
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        ':event_type' => $dbEventType,
        ':description' => $fullDescription,
        ':reported_by' => $systemUserId,
        ':event_subcategory' => $secondaryCategory
    ]);
    
    $dbEventId = $pdo->lastInsertId();
    
    // Save attachments to database
    if (!empty($attachments)) {
        $attachmentStmt = $pdo->prepare(
            "INSERT INTO attachments (event_id, file_name, file_type, file_size, file_path, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($attachments as $attachment) {
            // Determine file type from file name
            $fileExtension = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
            $fileType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
            
            $attachmentStmt->execute([
                $dbEventId,
                $attachment['name'],
                $fileType,
                $attachment['size'],
                $attachment['path'],
                $systemUserId  // Use the anonymous system user ID
            ]);
        }
    }
    
    // Check if user wants to send emails
    $sendEmails = isset($_POST['send_emails']) && $_POST['send_emails'] === '1';
    $emailSent = 0;
    $emailMessage = '';
    
    if ($sendEmails) {
        // Get specific people with email addresses (people_id = 5 and 29)
        // IsActive is tinyint(1), so we check for = 1 or = TRUE
        $query = "SELECT Email, FirstName, LastName FROM people 
                  WHERE people_id IN (5, 29) 
                  AND Email IS NOT NULL 
                  AND TRIM(Email) != '' 
                  AND (IsActive = 1 OR IsActive = TRUE)";
        $stmt = $pdo->query($query);
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($recipients)) {
            // Log recipients for debugging
            error_log("Email recipients found: " . count($recipients));
            foreach ($recipients as $recipient) {
                error_log("Recipient: " . $recipient['Email'] . " (" . $recipient['FirstName'] . " " . $recipient['LastName'] . ")");
            }
            
            // Send emails
            $emailSent = sendEventEmails($recipients, $eventData, $pdfPath, $attachments);
            $emailMessage = " Email notifications have been sent to " . $emailSent . " out of " . count($recipients) . " recipient(s).";
            
            if ($emailSent < count($recipients)) {
                $emailMessage .= " Some emails may have failed to send (check server logs).";
            }
        } else {
            $emailMessage = " No email recipients found in the system (people_id = 5 and 29 with valid emails).";
            error_log("No email recipients found for people_id = 5 and 29");
        }
    } else {
        $emailMessage = " Report saved without sending email notifications.";
    }

    echo json_encode([
        "success" => true,
        "message" => "Event reported successfully!" . $emailMessage,
        "event_id" => $eventId,
        "db_event_id" => $dbEventId,
        "emails_sent" => $emailSent
    ]);

} catch (Exception $e) {
    error_log("Anonymous Event Submission Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "An error occurred while processing your report. Please try again later."
    ]);
}

/**
 * Generate PDF report for the event
 */
function generateEventPDF($eventData, $attachments) {
    $pdfPath = 'uploads/anonymous_events/' . $eventData['event_id'] . '_report.pdf';
    
    // Ensure directory exists
    $dir = dirname($pdfPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Try to use TCPDF if available
    if (file_exists('vendor/tecnickcom/tcpdf/tcpdf.php')) {
        require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
        return generatePDFWithTCPDF($eventData, $attachments, $pdfPath);
    } 
    // Try FPDF if available
    elseif (file_exists('fpdf/fpdf.php')) {
        require_once 'fpdf/fpdf.php';
        return generatePDFWithFPDF($eventData, $attachments, $pdfPath);
    } 
    // Fallback: Create HTML that can be printed as PDF
    else {
        return generatePDFSimple($eventData, $attachments, $pdfPath);
    }
}

/**
 * Generate PDF using TCPDF (if available)
 */
function generatePDFWithTCPDF($eventData, $attachments, $pdfPath) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('SHEEner MS');
    $pdf->SetAuthor('SHEEner MS');
    $pdf->SetTitle('Event Report - ' . $eventData['event_id']);
    $pdf->SetSubject('Anonymous Event Report');
    
    // Remove default header/footer
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Header with background
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, $pdf->getPageWidth(), 20, 'F');
    
    // Logo (if available)
    $logoPath = 'img/Amneal_Logo_new.svg';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 20, 3, 30.4, 9, 'PNG', '', '', false, 300, '', false, false, 0);
    }
    
    // Header text
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(0, 6);
    $pdf->Cell(0, 10, 'Event Report', 0, 0, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(0, 6);
    $pdf->Cell(0, 10, 'Generated: ' . date('d M Y H:i'), 0, 0, 'R');
    
    // Content
    $pdf->SetY(30);
    $pdf->SetTextColor(0, 0, 0);
    
    // Event ID with QR code placeholder
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Event ID: ' . $eventData['event_id'], 0, 1);
    
    // Generate QR code
    $qrCodePath = generateQRCode($eventData['event_id']);
    if ($qrCodePath && file_exists($qrCodePath)) {
        $pdf->Image($qrCodePath, $pdf->getPageWidth() - 45, 25, 25, 25, 'PNG', '', '', false, 300, '', false, false, 0);
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Event details
    $details = [
        'Reported By: ' . $eventData['reporter_name'],
        'Reporter Email: ' . ($eventData['reporter_email'] ?: 'Not provided'),
        'Primary Category: ' . $eventData['primary_category'],
        'Secondary Category: ' . $eventData['secondary_category'],
        'Event Date: ' . date('d M Y H:i', strtotime($eventData['event_date'])),
        'Location: ' . ($eventData['location'] ?: 'Not specified'),
        'Submitted Date: ' . date('d M Y H:i', strtotime($eventData['submitted_date']))
    ];
    
    foreach ($details as $detail) {
        $pdf->Cell(0, 7, $detail, 0, 1);
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Event Description:', 0, 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $eventData['description'], 0, 'L');
    
    // Attachments list
    if (!empty($attachments)) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Attachments:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        foreach ($attachments as $attachment) {
            $pdf->Cell(0, 6, '- ' . $attachment['name'] . ' (' . formatFileSize($attachment['size']) . ')', 0, 1);
        }
    }
    
    // Save PDF
    $pdf->Output($pdfPath, 'F');
    
    // Clean up QR code temp file
    if ($qrCodePath && file_exists($qrCodePath)) {
        unlink($qrCodePath);
    }
    
    return $pdfPath;
}

/**
 * Generate PDF using FPDF (if available)
 */
function generatePDFWithFPDF($eventData, $attachments, $pdfPath) {
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, 20, 'F');
    
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(0, 6);
    $pdf->Cell(0, 10, 'Event Report', 0, 0, 'C');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(0, 6);
    $pdf->Cell(0, 10, 'Generated: ' . date('d M Y H:i'), 0, 0, 'R');
    
    // Content
    $pdf->SetY(30);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'Event ID: ' . $eventData['event_id'], 0, 1);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    $details = [
        'Reported By: ' . $eventData['reporter_name'],
        'Reporter Email: ' . ($eventData['reporter_email'] ?: 'Not provided'),
        'Primary Category: ' . $eventData['primary_category'],
        'Secondary Category: ' . $eventData['secondary_category'],
        'Event Date: ' . date('d M Y H:i', strtotime($eventData['event_date'])),
        'Location: ' . ($eventData['location'] ?: 'Not specified'),
        'Submitted Date: ' . date('d M Y H:i', strtotime($eventData['submitted_date']))
    ];
    
    foreach ($details as $detail) {
        $pdf->Cell(0, 7, $detail, 0, 1);
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 7, 'Event Description:', 0, 1);
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, $eventData['description'], 0, 'L');
    
    if (!empty($attachments)) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, 'Attachments:', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        foreach ($attachments as $attachment) {
            $pdf->Cell(0, 6, '- ' . $attachment['name'] . ' (' . formatFileSize($attachment['size']) . ')', 0, 1);
        }
    }
    
    $pdf->Output('F', $pdfPath);
    return $pdfPath;
}

/**
 * Generate PDF using simple HTML approach (fallback)
 * Creates a well-formatted HTML file that can be printed as PDF
 */
function generatePDFSimple($eventData, $attachments, $pdfPath) {
    $html = generateEventHTML($eventData, $attachments);
    $htmlPath = str_replace('.pdf', '.html', $pdfPath);
    file_put_contents($htmlPath, $html);
    
    // Try to use wkhtmltopdf if available
    if (shell_exec('which wkhtmltopdf')) {
        $command = "wkhtmltopdf --page-size A4 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm " . escapeshellarg($htmlPath) . " " . escapeshellarg($pdfPath);
        shell_exec($command);
        if (file_exists($pdfPath)) {
            unlink($htmlPath);
            return $pdfPath;
        }
    }
    
    // Return HTML path if PDF conversion not available
    // The email will attach the HTML file instead
    return $htmlPath;
}

/**
 * Generate HTML for event report
 */
function generateEventHTML($eventData, $attachments) {
    $qrCodeUrl = generateQRCodeURL($eventData['event_id']);
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Report - ' . htmlspecialchars($eventData['event_id']) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            background-color: #0a2f64;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
        }
        .content {
            max-width: 800px;
            margin: 0 auto;
        }
        .qr-code {
            float: right;
            text-align: center;
            margin: 10px;
        }
        .qr-code img {
            width: 100px;
            height: 100px;
        }
        h2 {
            color: #0a2f64;
            border-bottom: 2px solid #0a2f64;
            padding-bottom: 5px;
        }
        .detail-row {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
        .description {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #0a2f64;
        }
        .description h3 {
            margin-top: 0;
            color: #0a2f64;
        }
        .description p {
            white-space: pre-wrap;
            line-height: 1.6;
        }
        ul {
            list-style-type: disc;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Event Report</h1>
        <p>Generated: ' . date('d M Y H:i') . '</p>
    </div>
    <div class="content">
        <div class="qr-code">
            <img src="' . $qrCodeUrl . '" alt="QR Code">
            <p style="text-align: center; font-size: 10px;">QR Code</p>
        </div>
        <h2>Event ID: ' . htmlspecialchars($eventData['event_id']) . '</h2>
        <div class="detail-row"><span class="label">Reported By:</span> ' . htmlspecialchars($eventData['reporter_name']) . '</div>
        <div class="detail-row"><span class="label">Reporter Email:</span> ' . htmlspecialchars($eventData['reporter_email'] ?: 'Not provided') . '</div>
        <div class="detail-row"><span class="label">Primary Category:</span> ' . htmlspecialchars($eventData['primary_category']) . '</div>
        <div class="detail-row"><span class="label">Secondary Category:</span> ' . htmlspecialchars($eventData['secondary_category']) . '</div>
        <div class="detail-row"><span class="label">Event Date:</span> ' . date('d M Y H:i', strtotime($eventData['event_date'])) . '</div>
        <div class="detail-row"><span class="label">Location:</span> ' . htmlspecialchars($eventData['location'] ?: 'Not specified') . '</div>
        <div class="detail-row"><span class="label">Submitted Date:</span> ' . date('d M Y H:i', strtotime($eventData['submitted_date'])) . '</div>
        <div class="description">
            <h3>Event Description:</h3>
            <p>' . nl2br(htmlspecialchars($eventData['description'])) . '</p>
        </div>';
    
    if (!empty($attachments)) {
        $html .= '<div style="margin-top: 20px;"><h3>Attachments:</h3><ul>';
        foreach ($attachments as $attachment) {
            $html .= '<li>' . htmlspecialchars($attachment['name']) . ' (' . formatFileSize($attachment['size']) . ')</li>';
        }
        $html .= '</ul></div>';
    }
    
    $html .= '</div>
</body>
</html>';
    return $html;
}

/**
 * Generate QR code for event ID
 */
function generateQRCode($eventId) {
    // Try to use phpqrcode library if available
    if (file_exists('phpqrcode/qrlib.php')) {
        require_once 'phpqrcode/qrlib.php';
        $tempDir = 'uploads/temp_qr/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $qrPath = $tempDir . $eventId . '.png';
        QRcode::png($eventId, $qrPath, QR_ECLEVEL_L, 10, 2);
        return $qrPath;
    }
    
    // Fallback: Use online QR code API
    return null;
}

/**
 * Generate QR code URL (fallback)
 */
function generateQRCodeURL($eventId) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($eventId);
}

/**
 * Send emails to all recipients
 */
function sendEventEmails($recipients, $eventData, $pdfPath, $attachments) {
    $sent = 0;
    $subject = 'Event Report - ' . $eventData['event_id'] . ' - ' . $eventData['primary_category'];
    
    $message = "Hello,\n\n";
    $message .= "A new event has been reported through the anonymous reporting system.\n\n";
    $message .= "Event Details:\n";
    $message .= "----------------------------------------\n";
    $message .= "Event ID: " . $eventData['event_id'] . "\n";
    $message .= "Reported By: " . $eventData['reporter_name'] . "\n";
    if ($eventData['reporter_email']) {
        $message .= "Reporter Email: " . $eventData['reporter_email'] . "\n";
    }
    $message .= "Primary Category: " . $eventData['primary_category'] . "\n";
    $message .= "Secondary Category: " . $eventData['secondary_category'] . "\n";
    $message .= "Event Date: " . date('d M Y H:i', strtotime($eventData['event_date'])) . "\n";
    if ($eventData['location']) {
        $message .= "Location: " . $eventData['location'] . "\n";
    }
    $message .= "Submitted Date: " . date('d M Y H:i', strtotime($eventData['submitted_date'])) . "\n";
    $message .= "----------------------------------------\n\n";
    $message .= "Description:\n" . $eventData['description'] . "\n\n";
    
    if (!empty($attachments)) {
        $message .= "Attachments: " . count($attachments) . " file(s) attached\n";
    }
    
    $message .= "\nPlease review the attached PDF report for complete details.\n\n";
    $message .= "Best regards,\nSHEEner MS - Event Reporting System";
    
    $headers = "From: SHEEner MS <noreply@sheener.com>\r\n";
    $headers .= "Reply-To: " . ($eventData['reporter_email'] ?: 'noreply@sheener.com') . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"boundary123\"\r\n";
    
    foreach ($recipients as $recipient) {
        $email = $recipient['Email'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Create multipart message
            $body = "--boundary123\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $message . "\r\n\r\n";
            
            // Attach PDF or HTML report
            if (file_exists($pdfPath)) {
                $fileExtension = pathinfo($pdfPath, PATHINFO_EXTENSION);
                $contentType = ($fileExtension === 'pdf') ? 'application/pdf' : 'text/html';
                $body .= "--boundary123\r\n";
                $body .= "Content-Type: " . $contentType . "\r\n";
                $body .= "Content-Disposition: attachment; filename=\"" . basename($pdfPath) . "\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode(file_get_contents($pdfPath))) . "\r\n";
            }
            
            // Attach other files
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $body .= "--boundary123\r\n";
                    $body .= "Content-Type: application/octet-stream\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"" . $attachment['name'] . "\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= chunk_split(base64_encode(file_get_contents($attachment['path']))) . "\r\n";
                }
            }
            
            $body .= "--boundary123--\r\n";
            
            // Attempt to send email
            $mailResult = @mail($email, $subject, $body, $headers);
            
            if ($mailResult) {
                $sent++;
                error_log("Email sent successfully to: " . $email);
            } else {
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : 'Unknown error';
                error_log("Failed to send email to: " . $email . " - Error: " . $errorMsg);
            }
        } else {
            error_log("Invalid email address skipped: " . $email);
        }
    }
    
    return $sent;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}


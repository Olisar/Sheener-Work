<?php
/* File: sheener/php/verify_certificate.php */

header('Content-Type: application/json');
include 'database.php';

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'GET method required'], 405);
}

$qrValue = $_GET['qr'] ?? null;

if (!$qrValue) {
    jsonResponse(['error' => 'QR value is required'], 400);
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Look up certificate by QR value
    // Check both quiz_attempts and trainingattempts tables
    $stmt = $pdo->prepare('
        SELECT 
            qr_value,
            pass_datetime as issue_date,
            score,
            total,
            percentage,
            person_id,
            quiz_id
        FROM quiz_attempts 
        WHERE qr_value = ? AND passed = 1
        LIMIT 1
    ');
    $stmt->execute([$qrValue]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cert) {
        // Try alternate format - check if qr_value matches certificate ID pattern
        jsonResponse([
            'valid' => false,
            'error' => 'Certificate not found in system',
            'qr_value' => $qrValue
        ], 404);
    }
    
    // Get person name if person_id exists
    $name = 'Visitor';
    if ($cert['person_id']) {
        $stmt = $pdo->prepare('SELECT CONCAT(FirstName, " ", LastName) as name FROM people WHERE Person_ID = ?');
        $stmt->execute([$cert['person_id']]);
        $personData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($personData) {
            $name = $personData['name'];
        }
    }
    
    // Calculate expiry (1 year from issue date)
    $issueDate = new DateTime($cert['issue_date']);
    $expiryDate = clone $issueDate;
    $expiryDate->modify('+1 year');
    $now = new DateTime();
    
    // Calculate days remaining
    $interval = $now->diff($expiryDate);
    $daysRemaining = (int)$interval->format('%r%a'); // %r gives sign, %a gives days
    
    $isExpired = $now > $expiryDate;
    
    // Get quiz title
    $quizTitle = 'EHS Induction Quiz';
    if ($cert['quiz_id']) {
        $stmt = $pdo->prepare('SELECT title FROM quizzes WHERE id = ?');
        $stmt->execute([$cert['quiz_id']]);
        $quizData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($quizData) {
            $quizTitle = $quizData['title'];
        }
    }
    
    jsonResponse([
        'valid' => true,
        'expired' => $isExpired,
        'name' => $name,
        'cert_id' => $qrValue,
        'qr_value' => $qrValue,
        'issue_date' => $cert['issue_date'],
        'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
        'days_remaining' => $daysRemaining,
        'score' => (int)$cert['score'],
        'total' => (int)$cert['total'],
        'percentage' => round((float)$cert['percentage'], 2),
        'quiz_title' => $quizTitle
    ]);
    
} catch (PDOException $e) {
    error_log("verify_certificate.php PDOException: " . $e->getMessage());
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("verify_certificate.php Exception: " . $e->getMessage());
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
